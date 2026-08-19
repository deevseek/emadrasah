<?php

declare(strict_types=1);

namespace App\Services\Banking;

use App\Exceptions\BriApiException;
use App\Models\PayrollDisbursement;
use App\Services\Finance\BriConfigurationService;
use App\Services\Finance\BriSnapBiClient;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

final class BriPayrollTransferService
{
    public function __construct(private BriSnapBiClient $client, private BriConfigurationService $configuration) {}

    public function submit(PayrollDisbursement $disbursement): PayrollDisbursement
    {
        return DB::transaction(function () use ($disbursement): PayrollDisbursement {
            $disbursement = PayrollDisbursement::query()->lockForUpdate()->findOrFail($disbursement->id);
            if (! in_array($disbursement->status, ['pending', 'failed'], true)) return $disbursement;
            $batch = $disbursement->paymentBatch()->lockForUpdate()->firstOrFail();
            if ($batch->status !== 'processing') throw new BriApiException('Batch payroll belum disetujui dan dieksekusi.');
            $account = $disbursement->bank_account_snapshot;
            $isBri = in_array((string) ($account['bank_code'] ?? ''), ['002', 'BRI'], true);
            if (! $isBri && ! preg_match('/^\d{3}$/', (string) ($account['bank_code'] ?? ''))) {
                $disbursement->update(['status' => 'validation_failed', 'failure_message' => 'Kode bank tidak valid.']);
                return $disbursement;
            }
            $reference = $disbursement->partner_reference_no ?: str_replace('-', '', $disbursement->external_id);
            $payload = [
                'sourceAccountNo' => $this->configuration->sourceAccount(),
                'beneficiaryAccountNo' => $account['account_number'],
                'amount' => Money::idr($disbursement->amount),
                'partnerReferenceNo' => $reference,
                'customerReference' => $disbursement->external_id,
                'transactionDate' => now()->format('Y-m-d\\TH:i:sP'),
                'additionalInfo' => $isBri ? new \stdClass : ['beneficiaryBankCode' => $account['bank_code']],
            ];
            $disbursement->update(['partner_reference_no' => $reference, 'status' => 'submitting', 'submitted_at' => now()]);
            try {
                $response = $this->client->post((string) $this->configuration->path($isBri ? 'intrabank_transfer' : 'interbank_transfer'), $payload, $disbursement->external_id);
                $disbursement->update(['status' => 'pending', 'provider_reference' => $response->json('referenceNo'), 'response_code' => $response->json('responseCode'), 'response_message' => $response->json('responseMessage')]);
            } catch (BriApiException $exception) {
                $disbursement->update(['status' => $exception->outcomeUnknown ? 'pending_confirmation' : 'failed', 'response_code' => $exception->responseCode, 'response_message' => $exception->getMessage()]);
            }
            return $disbursement->refresh();
        });
    }
}
