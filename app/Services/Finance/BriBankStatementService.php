<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Exceptions\BriApiException;
use App\Models\Bank\BankTransaction;
use Illuminate\Support\Facades\DB;

final class BriBankStatementService
{
    public function __construct(private BriSnapBiClient $client, private BriConfigurationService $configuration) {}

    /** @param array<string,mixed> $documentedRequest */
    public function sync(array $documentedRequest): int
    {
        // Dates/pagination are supplied by the scheduled caller; the registered account can never be overridden.
        $documentedRequest['accountNo'] = $this->configuration->registeredAccountNumber();
        $response = $this->client->post((string) $this->configuration->path('bank_statement'), $documentedRequest)->json();
        $rows = data_get($response, 'detailData', []);
        if (! is_array($rows)) throw new BriApiException('Format Bank Statement BRI tidak valid.');

        $inserted = 0;
        foreach ($rows as $row) {
            $reference = (string) ($row['referenceNo'] ?? '');
            if ($reference === '') continue;
            DB::transaction(function () use ($row, $reference, &$inserted): void {
                $transaction = BankTransaction::query()->firstOrCreate(
                    ['provider' => 'BRI', 'provider_reference' => $reference],
                    ['external_id' => $reference, 'transaction_type' => 'bank_statement', 'amount' => data_get($row, 'amount.value', '0.00'), 'status' => 'unmatched', 'occurred_at' => $row['transactionDate'] ?? null, 'raw_payload' => $row],
                );
                if ($transaction->wasRecentlyCreated) $inserted++;
            });
        }
        return $inserted;
    }
}
