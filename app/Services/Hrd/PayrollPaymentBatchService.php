<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Models\PayrollDisbursement;
use App\Models\PayrollPaymentBatch;
use App\Models\PersonnelBankAccount;
use App\Models\PersonnelPayroll;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PayrollPaymentBatchService
{
    /** @param array<int,int> $payrollIds */
    public function create(array $payrollIds, User $maker): PayrollPaymentBatch
    {
        return DB::transaction(function () use ($payrollIds, $maker): PayrollPaymentBatch {
            $payrolls = PersonnelPayroll::query()->whereKey($payrollIds)->whereIn('status', ['processed', 'approved'])->lockForUpdate()->get();
            if ($payrolls->count() !== count(array_unique($payrollIds))) throw ValidationException::withMessages(['payroll' => 'Payroll belum valid atau sudah masuk batch.']);
            $batch = PayrollPaymentBatch::create(['batch_number' => 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)), 'payroll_period' => $payrolls->min('period_start').' / '.$payrolls->max('period_end'), 'total_records' => $payrolls->count(), 'total_amount' => $payrolls->sum('total'), 'status' => 'awaiting_approval', 'created_by' => $maker->id, 'external_id' => (string) Str::uuid()]);
            foreach ($payrolls as $payroll) {
                $account = PersonnelBankAccount::query()->where('personnel_id', $payroll->personnel_id)->where('is_primary', true)->firstOrFail();
                PayrollDisbursement::create(['payroll_payment_batch_id' => $batch->id, 'personnel_payroll_id' => $payroll->id, 'personnel_id' => $payroll->personnel_id, 'bank_account_snapshot' => ['bank_code' => $account->bank_code, 'account_number' => $account->account_number, 'holder' => $account->account_holder_name], 'amount' => $payroll->total, 'provider' => 'BRI', 'external_id' => (string) Str::uuid(), 'status' => 'pending']);
            }
            activity('payroll')->causedBy($maker)->performedOn($batch)->log('Membuat batch pembayaran payroll.');
            return $batch;
        });
    }

    public function approve(PayrollPaymentBatch $batch, User $checker): void
    {
        DB::transaction(function () use ($batch, $checker): void {
            $batch = PayrollPaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($batch->created_by === $checker->id) throw new AuthorizationException('Pembuat batch tidak boleh menjadi approver.');
            if ($batch->status !== 'awaiting_approval') throw ValidationException::withMessages(['status' => 'Batch tidak menunggu persetujuan.']);
            $batch->update(['status' => 'approved', 'approved_by' => $checker->id, 'approved_at' => now()]);
            activity('payroll')->causedBy($checker)->performedOn($batch)->log('Menyetujui batch pembayaran payroll.');
        });
    }

    public function beginExecution(PayrollPaymentBatch $batch, User $executor): PayrollPaymentBatch
    {
        return DB::transaction(function () use ($batch, $executor): PayrollPaymentBatch {
            $batch = PayrollPaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($batch->status !== 'approved') throw ValidationException::withMessages(['status' => 'Batch wajib disetujui sebelum dibayar.']);
            $batch->update(['status' => 'processing', 'executed_by' => $executor->id, 'executed_at' => now()]);
            activity('payroll')->causedBy($executor)->performedOn($batch)->log('Memulai eksekusi pembayaran payroll.');
            return $batch;
        });
    }

    public function finalize(PayrollPaymentBatch $batch): PayrollPaymentBatch
    {
        return DB::transaction(function () use ($batch): PayrollPaymentBatch {
            $batch = PayrollPaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $statuses = $batch->disbursements()->pluck('status');
            $status = $statuses->contains(fn ($value) => in_array($value, ['pending', 'submitting', 'pending_confirmation'], true))
                ? 'reconciliation_required'
                : ($statuses->every(fn ($value) => $value === 'succeeded') ? 'completed' : ($statuses->contains('succeeded') ? 'partially_completed' : 'approved'));
            $batch->update(['status' => $status, 'completed_at' => $status === 'completed' ? now() : null]);
            return $batch;
        });
    }
}
