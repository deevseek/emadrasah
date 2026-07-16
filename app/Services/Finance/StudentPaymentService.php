<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentPaymentService
{
    public function post(array $data, array $allocations): StudentPayment
    {
        return DB::transaction(function () use ($data, $allocations): StudentPayment {
            $cashAccount = $this->resolveCashAccount($data['cash_account_id'] ?? null);
            $total = '0';
            $studentId = (int) $data['student_id'];
            $journalLines = [];
            $lockedInvoices = [];

            foreach ($allocations as $index => $allocation) {
                $invoiceId = (int) $allocation['student_invoice_id'];

                if (isset($lockedInvoices[$invoiceId])) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.student_invoice_id" => 'Tagihan tidak boleh dialokasikan lebih dari satu kali.',
                    ]);
                }

                $invoice = StudentInvoice::query()
                    ->with('feeType')
                    ->lockForUpdate()
                    ->findOrFail($invoiceId);

                if ((int) $invoice->student_id !== $studentId) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.student_invoice_id" => 'Tagihan harus milik siswa yang sama.',
                    ]);
                }

                if (in_array($invoice->status, [
                    InvoiceStatus::Cancelled->value,
                    InvoiceStatus::Paid->value,
                ], true)) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.student_invoice_id" => 'Tagihan tidak dapat dibayar.',
                    ]);
                }

                $amount = (string) $allocation['amount'];

                if (bccomp($amount, '0', 2) <= 0) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.amount" => 'Alokasi harus lebih dari nol.',
                    ]);
                }

                if (bccomp($amount, (string) $invoice->outstanding_amount, 2) > 0) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.amount" => 'Pembayaran melebihi sisa tagihan.',
                    ]);
                }

                $revenueAccountId = $this->resolveRevenueAccountId($invoice, $index);
                $total = bcadd($total, $amount, 2);
                $lockedInvoices[$invoiceId] = $invoice;
                $journalLines[] = [
                    'chart_account_id' => $revenueAccountId,
                    'cash_account_id' => null,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => $invoice->invoice_number,
                ];
            }

            if (bccomp($total, (string) $data['total_amount'], 2) !== 0) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Total alokasi harus sama dengan total pembayaran.',
                ]);
            }

            array_unshift($journalLines, [
                'chart_account_id' => $cashAccount->chart_account_id,
                'cash_account_id' => $cashAccount->getKey(),
                'debit' => $total,
                'credit' => 0,
                'description' => 'Penerimaan pembayaran siswa',
            ]);

            $transaction = app(FinancialTransactionService::class)->createAndPost([
                'transaction_date' => $data['payment_date'],
                'transaction_type' => TransactionType::CashIn->value,
                'description' => 'Pembayaran siswa',
                'reference_type' => StudentPayment::class,
                'created_by' => auth()->id(),
            ], $journalLines);

            $payment = StudentPayment::create(
                Arr::except($data, ['cash_account_id']) + [
                    'payment_number' => app(DocumentNumberService::class)
                        ->next('BYR', 'BYR/{YEAR}/{MONTH}/{SEQ}'),
                    'status' => PaymentStatus::Posted->value,
                    'financial_transaction_id' => $transaction->getKey(),
                ],
            );

            $transaction->update(['reference_id' => $payment->getKey()]);

            foreach ($allocations as $allocation) {
                $invoice = $lockedInvoices[(int) $allocation['student_invoice_id']];
                $amount = (string) $allocation['amount'];

                $payment->allocations()->create([
                    'student_invoice_id' => $invoice->getKey(),
                    'amount' => $amount,
                ]);

                $invoice->update([
                    'paid_amount' => bcadd((string) $invoice->paid_amount, $amount, 2),
                    'outstanding_amount' => bcsub((string) $invoice->outstanding_amount, $amount, 2),
                ]);

                app(StudentInvoiceService::class)->refreshStatus($invoice->refresh());
            }

            activity('student-finance')
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->event('payment.posted')
                ->log('Pembayaran siswa diposting');

            return $payment->load('student', 'allocations.invoice.feeType', 'financialTransaction');
        });
    }

    public function cancel(StudentPayment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason): void {
            $payment = StudentPayment::query()
                ->with(['allocations', 'financialTransaction'])
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            if ($payment->status !== PaymentStatus::Posted->value) {
                throw ValidationException::withMessages([
                    'payment' => 'Hanya pembayaran berstatus posted yang dapat dibatalkan.',
                ]);
            }

            if ($payment->financialTransaction === null) {
                throw ValidationException::withMessages([
                    'payment' => 'Jurnal pembayaran tidak ditemukan.',
                ]);
            }

            foreach ($payment->allocations as $allocation) {
                $invoice = StudentInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail($allocation->student_invoice_id);
                $newPaidAmount = bcsub(
                    (string) $invoice->paid_amount,
                    (string) $allocation->amount,
                    2,
                );

                if (bccomp($newPaidAmount, '0', 2) < 0) {
                    throw ValidationException::withMessages([
                        'payment' => 'Nilai pembayaran tagihan tidak konsisten dan pembatalan dihentikan.',
                    ]);
                }

                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'outstanding_amount' => bcadd(
                        (string) $invoice->outstanding_amount,
                        (string) $allocation->amount,
                        2,
                    ),
                ]);

                app(StudentInvoiceService::class)->refreshStatus($invoice->refresh());
            }

            app(FinancialTransactionService::class)->reverse(
                $payment->financialTransaction,
                $reason,
            );

            $payment->update([
                'status' => PaymentStatus::Cancelled->value,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            activity('student-finance')
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->withProperties(['reason' => $reason])
                ->event('payment.cancelled')
                ->log('Pembayaran siswa dibatalkan');
        });
    }

    private function resolveCashAccount(mixed $cashAccountId): CashAccount
    {
        $cashAccount = CashAccount::query()
            ->where('is_active', true)
            ->when(
                filled($cashAccountId),
                fn ($query) => $query->whereKey($cashAccountId),
            )
            ->lockForUpdate()
            ->first();

        if ($cashAccount === null) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Akun kas aktif tidak ditemukan.',
            ]);
        }

        return $cashAccount;
    }

    private function resolveRevenueAccountId(
        StudentInvoice $invoice,
        int $allocationIndex,
    ): int {
        $revenueAccountId = $invoice->feeType?->revenue_account_id;

        if ($revenueAccountId === null) {
            $revenueAccountId = ChartAccount::query()
                ->where('account_type', 'revenue')
                ->where('is_active', true)
                ->orderBy('sequence')
                ->value('id');
        }

        if ($revenueAccountId === null || ! ChartAccount::query()->whereKey($revenueAccountId)->exists()) {
            throw ValidationException::withMessages([
                "allocations.{$allocationIndex}.student_invoice_id" => 'Akun pendapatan untuk jenis tagihan belum dikonfigurasi.',
            ]);
        }

        return (int) $revenueAccountId;
    }
}
