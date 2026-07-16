<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentPaymentService
{
    public function post(array $data, array $allocations): StudentPayment
    {
        return DB::transaction(function () use ($data, $allocations): StudentPayment {
            $total = '0';
            $studentId = $data['student_id'];
            foreach ($allocations as $allocation) {
                $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($allocation['student_invoice_id']);
                throw_if($invoice->student_id !== $studentId, ValidationException::withMessages(['allocations' => 'Tagihan harus milik siswa yang sama.']));
                throw_if(bccomp((string) $allocation['amount'], (string) $invoice->outstanding_amount, 2) > 0, ValidationException::withMessages(['allocations' => 'Pembayaran melebihi sisa tagihan.']));
                $total = bcadd($total, (string) $allocation['amount'], 2);
            }
            throw_if(bccomp($total, (string) $data['total_amount'], 2) !== 0, ValidationException::withMessages(['total_amount' => 'Total alokasi harus sama dengan total pembayaran.']));
            $cash = CashAccount::query()->where('is_active', true)->firstOrFail();
            $revenue = ChartAccount::query()->where('account_type', 'revenue')->firstOrFail();
            $trx = app(FinancialTransactionService::class)->createAndPost([
                'transaction_date' => $data['payment_date'],
                'transaction_type' => TransactionType::CashIn->value,
                'description' => 'Pembayaran siswa',
                'reference_type' => StudentPayment::class,
                'created_by' => auth()->id(),
            ], [
                ['chart_account_id' => $cash->chart_account_id, 'cash_account_id' => $cash->id, 'debit' => $total, 'credit' => 0],
                ['chart_account_id' => $revenue->id, 'debit' => 0, 'credit' => $total],
            ]);
            $payment = StudentPayment::create($data + [
                'payment_number' => app(DocumentNumberService::class)->next('BYR', 'BYR/{YEAR}/{MONTH}/{SEQ}'),
                'status' => PaymentStatus::Posted->value,
                'financial_transaction_id' => $trx->id,
            ]);
            foreach ($allocations as $allocation) {
                $payment->allocations()->create($allocation);
                $invoice = StudentInvoice::query()->lockForUpdate()->find($allocation['student_invoice_id']);
                $invoice->update([
                    'paid_amount' => bcadd((string) $invoice->paid_amount, (string) $allocation['amount'], 2),
                    'outstanding_amount' => bcsub((string) $invoice->outstanding_amount, (string) $allocation['amount'], 2),
                ]);
                app(StudentInvoiceService::class)->refreshStatus($invoice->refresh());
            }
            activity('student-finance')->performedOn($payment)->causedBy(auth()->user())->event('payment.posted')->log('Pembayaran siswa diposting');

            return $payment;
        });
    }

    public function cancel(StudentPayment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason): void {
            $payment->refresh();
            throw_if($payment->status === PaymentStatus::Cancelled->value, ValidationException::withMessages(['payment' => 'Pembayaran sudah dibatalkan.']));
            foreach ($payment->allocations as $allocation) {
                $invoice = StudentInvoice::query()->lockForUpdate()->find($allocation->student_invoice_id);
                $invoice->update([
                    'paid_amount' => bcsub((string) $invoice->paid_amount, (string) $allocation->amount, 2),
                    'outstanding_amount' => bcadd((string) $invoice->outstanding_amount, (string) $allocation->amount, 2),
                ]);
                app(StudentInvoiceService::class)->refreshStatus($invoice->refresh());
            }
            $payment->update(['status' => PaymentStatus::Cancelled->value, 'cancelled_by' => auth()->id(), 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
            activity('student-finance')->performedOn($payment)->causedBy(auth()->user())->event('payment.cancelled')->log('Pembayaran siswa dibatalkan');
        });
    }
}
