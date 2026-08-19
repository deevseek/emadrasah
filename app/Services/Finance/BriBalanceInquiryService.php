<?php

declare(strict_types=1);

namespace App\Services\Finance;

use RuntimeException;

class BriBalanceInquiryService
{
    public function __construct(private BriSnapBiClient $client) {}

    /** @return array<string,mixed> */
    public function inquiry(string $accountNumber): array
    {
        $accountNumber = preg_replace('/\D+/', '', $accountNumber) ?? '';
        if ($accountNumber === '') throw new RuntimeException('Nomor rekening BRI wajib diisi.');

        return $this->client->post('/snap/v1.0/balance-inquiry', [
            'accountNo' => $accountNumber,
        ])->json();
    }
}
