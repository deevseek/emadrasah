<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Contracts\Banking\BankTransferGateway;
use App\Models\{PayrollDisbursement,PayrollPaymentBatch,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayrollDisbursementService
{
    public function __construct(private BankTransferGateway $gateway) {}

    public function execute(PayrollPaymentBatch $batch, User $actor): PayrollPaymentBatch
    {
        if ($batch->status !== 'approved') throw ValidationException::withMessages(['status'=>'Hanya batch payroll yang sudah disetujui yang dapat ditransfer.']);

        $batch->disbursements()->whereIn('status',['pending','failed'])->each(function(PayrollDisbursement $d): void {
            DB::transaction(function() use ($d): void {
                $locked = PayrollDisbursement::query()->lockForUpdate()->findOrFail($d->id);
                if (!in_array($locked->status,['pending','failed'],true)) return;
                $account = $locked->bank_account_snapshot;
                $locked->update(['status'=>'processing','submitted_at'=>now()]);
                try {
                    $result = $this->gateway->transfer([
                        'external_id'=>$locked->external_id,
                        'beneficiary_account'=>$account['account_number'],
                        'beneficiary_name'=>$account['holder'] ?? '',
                        'bank_code'=>$account['bank_code'] ?? 'BRI',
                        'amount'=>$locked->amount,
                        'remark'=>'Payroll e-Madrasah',
                    ]);
                    $locked->update([
                        'status'=>$result['status'] ?? 'pending',
                        'provider_reference'=>$result['provider_reference'] ?? null,
                        'completed_at'=>($result['status'] ?? null)==='succeeded' ? now() : null,
                        'failure_message'=>($result['status'] ?? null)==='failed' ? ($result['response_message'] ?? 'Transfer gagal') : null,
                    ]);
                } catch (Throwable $e) {
                    // Unknown outcome must stay pending: blindly retrying can double-pay salary.
                    $locked->update(['status'=>'pending','failure_message'=>'Status transfer belum dapat dipastikan: '.$e->getMessage()]);
                }
            });
        });

        $batch->refresh();
        $statuses = $batch->disbursements()->pluck('status');
        $status = $statuses->every(fn($s)=>$s==='succeeded') ? 'completed' : ($statuses->contains('processing') || $statuses->contains('pending') ? 'processing' : 'failed');
        $batch->update(['status'=>$status,'executed_at'=>$status==='completed'?now():$batch->executed_at]);
        activity('payroll')->causedBy($actor)->performedOn($batch)->log('Menjalankan pembayaran payroll melalui gateway bank.');
        return $batch->fresh();
    }
}
