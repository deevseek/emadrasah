<?php

declare(strict_types=1);

namespace App\Services\Finance;

use RuntimeException;

class BriBalanceInquiryService
{
    public function __construct(private BriSnapBiClient $client, private BriConfigurationService $configuration) {}

    /** @return array<string,mixed> */
    public function inquiry(): array
    {
        $accountNumber = (string) $this->configuration->registeredAccountNumber();
        $accountNumber = preg_replace('/\D+/', '', $accountNumber) ?? '';
        if ($accountNumber === '') throw new RuntimeException('Nomor rekening BRI wajib diisi.');

        return $this->client->post((string) $this->configuration->path('balance_inquiry'), [
            'accountNo' => $accountNumber,
        ])->json();
    }
}
