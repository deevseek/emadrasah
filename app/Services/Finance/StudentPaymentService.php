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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentPaymentService
{
    public function post(array $data, array $allocations): StudentPayment
    {
        return DB::transaction(function () use ($data, $allocations): StudentPayment {
            $total = '0';
            $studentId = $data['student_id'];
            $lines = [];
            foreach ($allocations as $allocation) {
                $invoice = StudentInvoice::query()->with('feeType')->lockForUpdate()->findOrFail($allocation['student_invoice_id']);
                throw_if($invoice->student_id !== $studentId, ValidationException::withMessages(['allocations' => 'Tagihan harus milik siswa yang sama.']));
                throw_if(in_array($invoice->status, [InvoiceStatus::Cancelled->value, InvoiceStatus::Paid->value], true), ValidationException::withMessages(['allocations' => 'Tagihan tidak dapat dibayar.']));
                throw_if(bccomp((string) $allocation['amount'], '0', 2) <= 0, ValidationException::withMessages(['allocations' => 'Alokasi harus lebih dari nol.']));
                throw_if(bccomp((string) $allocation['amount'], (string) $invoice->outstanding_amount, 2) > 0, ValidationException::withMessages(['allocations' => 'Pembayaran melebihi sisa tagihan.']));
                $total = bcadd($total, (string) $allocation['amount'], 2);
                $revenueId = $invoice->feeType?->revenue_account_id ?? ChartAccount::query()->where('account_type', 'revenue')->value('id');
                $lines[] = ['chart_account_id' => $revenueId, 'debit' => 0, 'credit' => $allocation['amount'], 'description' => $invoice->invoice_number];
            }
            throw_if(bccomp($total, (string) $data['total_amount'], 2) !== 0, ValidationException::withMessages(['total_amount' => 'Total alokasi harus sama dengan total pembayaran.']));
            $cash = CashAccount::query()->where('is_active', true)->firstOrFail();
            array_unshift($lines, ['chart_account_id' => $cash->chart_account_id, 'cash_account_id' => $cash->id, 'debit' => $total, 'credit' => 0]);
            $trx = app(FinancialTransactionService::class)->createAndPost(['transaction_date' => $data['payment_date'], 'transaction_type' => TransactionType::CashIn->value, 'description' => 'Pembayaran siswa', 'reference_type' => StudentPayment::class, 'created_by' => auth()->id()], $lines);
            $payment = StudentPayment::create($data + ['payment_number' => app(DocumentNumberService::class)->next('BYR', 'BYR/{YEAR}/{MONTH}/{SEQ}'), 'receipt_number' => app(DocumentNumberService::class)->next('KWT', 'KWT/{YEAR}/{MONTH}/{SEQ}'), 'status' => PaymentStatus::Posted->value, 'financial_transaction_id' => $trx->id]);
            $trx->update(['reference_id' => $payment->id]);
            foreach ($allocations as $allocation) {
                $payment->allocations()->create($allocation);
                $invoice = StudentInvoice::query()->lockForUpdate()->find($allocation['student_invoice_id']);
                $invoice->update(['paid_amount' => bcadd((string) $invoice->paid_amount, (string) $allocation['amount'], 2), 'outstanding_amount' => bcsub((string) $invoice->outstanding_amount, (string) $allocation['amount'], 2)]);
                app(StudentInvoiceService::class)->refreshStatus($invoice->refresh());
            }
            activity('student-finance')->performedOn($payment)->causedBy(auth()->user())->event('payment.posted')->log('Pembayaran siswa diposting');

            return $payment;
        });
    }

    public function cancel(StudentPayment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason): void {
            $payment = StudentPayment::query()->with('allocations')->lockForUpdate()->findOrFail($payment->id);
            throw_if($payment->status === PaymentStatus::Cancelled->value, ValidationException::withMessages(['payment' => 'Pembayaran sudah dibatalkan.']));
            foreach ($payment->allocations as $allocation) {
                $invoice = StudentInvoice::query()->lockForUpdate()->find($allocation->student_invoice_id);
                $invoice->update(['paid_amount' => bcsub((string) $invoice->paid_amount, (string) $allocation->amount, 2), 'outstanding_amount' => bcadd((string) $invoice->outstanding_amount, (string) $allocation->amount, 2)]);
                app(StudentInvoiceService::class)->refreshStatus($invoice->refresh());
            }
            if ($payment->financialTransaction) {
                app(FinancialTransactionService::class)->reverse($payment->financialTransaction, $reason);
            }
            $payment->update(['status' => PaymentStatus::Cancelled->value, 'cancelled_by' => auth()->id(), 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
            activity('student-finance')->performedOn($payment)->causedBy(auth()->user())->event('payment.cancelled')->log('Pembayaran siswa dibatalkan');
        });
    }
}
