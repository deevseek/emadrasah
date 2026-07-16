<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\PayrollStatus;
use App\Enums\Finance\SalaryComponentType;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\EmployeePayroll;
use App\Models\Finance\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PayrollWorkflowService
{
    public function review(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $period = $this->lockedPeriod($period);
            $this->ensureStatus($period, PayrollStatus::Calculated);

            if (! $period->payrolls()->exists()) {
                throw ValidationException::withMessages([
                    'period' => 'Payroll belum memiliki hasil perhitungan.',
                ]);
            }

            $period->payrolls()->update([
                'status' => PayrollStatus::Reviewed->value,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
            $period->update(['status' => PayrollStatus::Reviewed->value]);

            activity('payroll')
                ->performedOn($period)
                ->causedBy(auth()->user())
                ->event('payroll.reviewed')
                ->log('Payroll direview');
        });
    }

    public function approve(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $period = $this->lockedPeriod($period);
            $this->ensureStatus($period, PayrollStatus::Reviewed);

            $period->payrolls()->update([
                'status' => PayrollStatus::Approved->value,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $period->update(['status' => PayrollStatus::Approved->value]);

            activity('payroll')
                ->performedOn($period)
                ->causedBy(auth()->user())
                ->event('payroll.approved')
                ->log('Payroll disetujui');
        });
    }

    public function markPaid(PayrollPeriod $period, int $cashAccountId): void
    {
        DB::transaction(function () use ($period, $cashAccountId): void {
            $period = $this->lockedPeriod($period);
            $this->ensureStatus($period, PayrollStatus::Approved);

            $cashAccount = CashAccount::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->find($cashAccountId);

            if ($cashAccount === null) {
                throw ValidationException::withMessages([
                    'cash_account_id' => 'Rekening kas aktif tidak ditemukan.',
                ]);
            }

            $payrolls = $period->payrolls()
                ->with(['items.salaryComponent'])
                ->lockForUpdate()
                ->get();

            if ($payrolls->isEmpty()) {
                throw ValidationException::withMessages([
                    'period' => 'Tidak ada payroll pegawai yang dapat dibayar.',
                ]);
            }

            foreach ($payrolls as $payroll) {
                $this->payEmployee($period, $payroll, $cashAccount);
            }

            $period->update([
                'status' => PayrollStatus::Paid->value,
                'payment_date' => now()->toDateString(),
            ]);

            activity('payroll')
                ->performedOn($period)
                ->causedBy(auth()->user())
                ->withProperties(['cash_account_id' => $cashAccount->getKey()])
                ->event('payroll.paid')
                ->log('Payroll dibayar');
        });
    }

    public function close(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $period = $this->lockedPeriod($period);
            $this->ensureStatus($period, PayrollStatus::Paid);
            $period->update(['status' => PayrollStatus::Closed->value]);

            activity('payroll')
                ->performedOn($period)
                ->causedBy(auth()->user())
                ->event('payroll.closed')
                ->log('Payroll ditutup');
        });
    }

    public function reopen(PayrollPeriod $period, string $reason): void
    {
        DB::transaction(function () use ($period, $reason): void {
            $period = $this->lockedPeriod($period);
            $this->ensureStatus($period, PayrollStatus::Closed);

            $period->update(['status' => PayrollStatus::Paid->value]);

            activity('payroll')
                ->performedOn($period)
                ->causedBy(auth()->user())
                ->withProperties(['reason' => $reason])
                ->event('payroll.reopened')
                ->log('Payroll dibuka kembali ke status paid');
        });
    }

    private function payEmployee(
        PayrollPeriod $period,
        EmployeePayroll $payroll,
        CashAccount $cashAccount,
    ): void {
        if ($payroll->status !== PayrollStatus::Approved->value) {
            throw ValidationException::withMessages([
                'payroll' => "Payroll {$payroll->employee?->name} belum disetujui.",
            ]);
        }

        if ($payroll->financial_transaction_id !== null || $payroll->paid_at !== null) {
            throw ValidationException::withMessages([
                'payroll' => "Payroll {$payroll->employee?->name} sudah dibayar.",
            ]);
        }

        if (bccomp((string) $payroll->net_salary, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'payroll' => "Gaji bersih {$payroll->employee?->name} harus lebih dari nol.",
            ]);
        }

        $expenseAccountId = $payroll->items
            ->filter(static fn ($item): bool => $item->component_type === SalaryComponentType::Earning->value)
            ->pluck('salaryComponent.expense_account_id')
            ->filter()
            ->first();

        if ($expenseAccountId === null) {
            $expenseAccountId = ChartAccount::query()
                ->where('account_type', 'expense')
                ->where('is_active', true)
                ->orderBy('sequence')
                ->value('id');
        }

        if ($expenseAccountId === null) {
            throw ValidationException::withMessages([
                'payroll' => 'Akun beban payroll belum dikonfigurasi.',
            ]);
        }

        $transaction = app(FinancialTransactionService::class)->createAndPost([
            'transaction_date' => now()->toDateString(),
            'transaction_type' => TransactionType::CashOut->value,
            'description' => 'Pembayaran payroll '.$period->name.' - '.$payroll->employee?->name,
            'reference_type' => $payroll::class,
            'reference_id' => $payroll->getKey(),
            'created_by' => auth()->id(),
        ], [
            [
                'chart_account_id' => $expenseAccountId,
                'cash_account_id' => null,
                'debit' => $payroll->net_salary,
                'credit' => 0,
                'description' => 'Beban gaji '.$payroll->employee?->name,
            ],
            [
                'chart_account_id' => $cashAccount->chart_account_id,
                'cash_account_id' => $cashAccount->getKey(),
                'debit' => 0,
                'credit' => $payroll->net_salary,
                'description' => 'Pembayaran gaji '.$payroll->employee?->name,
            ],
        ]);

        $payroll->update([
            'status' => PayrollStatus::Paid->value,
            'paid_at' => now(),
            'payment_method' => $cashAccount->bank_name ? 'transfer_bank' : 'tunai',
            'reference_number' => $transaction->transaction_number,
            'financial_transaction_id' => $transaction->getKey(),
        ]);
    }

    private function lockedPeriod(PayrollPeriod $period): PayrollPeriod
    {
        return PayrollPeriod::query()
            ->lockForUpdate()
            ->findOrFail($period->getKey());
    }

    private function ensureStatus(
        PayrollPeriod $period,
        PayrollStatus $expected,
    ): void {
        if ($period->status !== $expected->value) {
            throw ValidationException::withMessages([
                'period' => 'Aksi hanya dapat dilakukan saat status payroll '.$expected->value.'.',
            ]);
        }
    }
}
