<?php

declare(strict_types=1);

namespace App\Services\Banking;

use App\Contracts\Banking\BankTransferGateway;
use App\Services\Finance\BriConfigurationService;
use App\Services\Finance\BriSnapBiClient;
use InvalidArgumentException;
use RuntimeException;

/**
 * BRI SNAP BI transfer gateway for approved payroll disbursements.
 *
 * This class intentionally does not calculate payroll or approve a batch.
 * It only executes an already-approved disbursement.
 */
class BriSnapBiTransferGateway implements BankTransferGateway
{
    public function __construct(
        private BriSnapBiClient $client,
        private BriConfigurationService $configuration,
    ) {}

    public function transfer(array $request): array
    {
        if (! $this->configuration->payrollEnabled()) {
            throw new RuntimeException('Pembayaran payroll melalui BRI belum diaktifkan.');
        }

        $destination = $this->required($request, 'beneficiary_account');
        $amount = $this->required($request, 'amount');
        $reference = $this->required($request, 'external_id');
        $source = (string) config('bri.payroll.source_account');
        if ($source === '') throw new RuntimeException('Rekening sumber payroll BRI belum dikonfigurasi.');

        $bankCode = strtoupper((string) ($request['bank_code'] ?? 'BRI'));

        // BRI-to-BRI payroll uses the Intrabank Transfer SNAP BI endpoint.
        if (in_array($bankCode, ['BRI', '002'], true)) {
            $payload = [
                'partnerReferenceNo' => $reference,
                'amount' => ['value' => number_format((float) $amount, 2, '.', ''), 'currency' => 'IDR'],
                'beneficiaryAccountNo' => $destination,
                'customerReference' => $reference,
                'sourceAccountNo' => $source,
                'transactionDate' => now()->format('c'),
                'additionalInfo' => ['remark' => (string) ($request['remark'] ?? 'Payroll e-Madrasah')],
            ];

            $response = $this->client->post('/snap/v1.0/transfer-intrabank', $payload);
        } else {
            // The interbank endpoint uses SNAP BI Transfer Interbank and requires a bank code.
            $payload = [
                'partnerReferenceNo' => $reference,
                'amount' => ['value' => number_format((float) $amount, 2, '.', ''), 'currency' => 'IDR'],
                'beneficiaryAccountName' => (string) ($request['beneficiary_name'] ?? ''),
                'beneficiaryAccountNo' => $destination,
                'beneficiaryBankCode' => $bankCode,
                'sourceAccountNo' => $source,
                'transactionDate' => now()->format('c'),
                'additionalInfo' => ['remark' => (string) ($request['remark'] ?? 'Payroll e-Madrasah')],
            ];

            $response = $this->client->post('/snap/v1.0/transfer-interbank', $payload);
        }

        return [
            'status' => $this->normalizeStatus((string) $response->json('responseCode')),
            'external_id' => $reference,
            'provider_reference' => $response->json('referenceNo') ?: $response->json('partnerReferenceNo'),
            'response_code' => $response->json('responseCode'),
            'response_message' => $response->json('responseMessage'),
        ];
    }

    public function inquire(string $externalId): array
    {
        // Product/service codes are supplied by BRI during onboarding and can differ
        // between intrabank/interbank products. Do not guess them in production.
        return [
            'status' => 'pending',
            'external_id' => $externalId,
            'message' => 'Status inquiry menunggu serviceCode resmi dari onboarding BRI.',
        ];
    }

    private function required(array $request, string $key): string
    {
        $value = $request[$key] ?? null;
        if ($value === null || trim((string) $value) === '') throw new InvalidArgumentException('Field '.$key.' wajib diisi.');
        return (string) $value;
    }

    private function normalizeStatus(string $code): string
    {
        return str_starts_with($code, '200') ? 'succeeded' : 'pending';
    }
}
