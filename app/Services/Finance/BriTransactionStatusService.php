<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\BriTransactionStatus;
use App\Exceptions\BriApiException;

final class BriTransactionStatusService
{
    public function __construct(private BriSnapBiClient $client, private BriConfigurationService $configuration) {}

    /** @return array{status:BriTransactionStatus,response:array<string,mixed>} */
    public function inquiry(string $originalPartnerReferenceNo, string $serviceCode, string $transactionDate, ?string $externalId = null): array
    {
        $response = $this->client->post((string) $this->configuration->path('transaction_status'), [
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
            'serviceCode' => $serviceCode,
            'transactionDate' => $transactionDate,
        ], $externalId)->json();

        return ['status' => $this->normalize((string) data_get($response, 'latestTransactionStatus')), 'response' => $response];
    }

    public function normalize(string $status): BriTransactionStatus
    {
        return match (strtolower($status)) {
            '00', 'success', 'successful', 'succeeded' => BriTransactionStatus::Succeeded,
            '01', 'pending', 'processing' => BriTransactionStatus::Pending,
            '02', 'failed', 'rejected' => BriTransactionStatus::Failed,
            '03', 'cancelled', 'canceled' => BriTransactionStatus::Cancelled,
            '04', 'expired' => BriTransactionStatus::Expired,
            default => BriTransactionStatus::Unknown,
        };
    }
}
