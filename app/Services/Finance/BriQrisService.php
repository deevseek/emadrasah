<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\BriQrisTransaction;
use App\Models\Finance\StudentInvoice;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BriQrisService
{
    public function __construct(private BriSnapBiClient $client, private BriConfigurationService $configuration) {}

    public function generate(StudentInvoice $invoice): BriQrisTransaction
    {
        if (! $this->configuration->qrisEnabled()) throw ValidationException::withMessages(['qris' => 'QRIS BRI tidak aktif.']);
        return DB::transaction(function () use ($invoice): BriQrisTransaction {
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $existing = BriQrisTransaction::query()->where('invoice_id', $invoice->id)->where('status', 'pending')->where('expires_at', '>', now())->first();
            if ($existing) return $existing;
            $reference = $this->numericReference();
            $expiresAt = now()->addMinutes(15);
            $response = $this->client->post((string) $this->configuration->path('qris_generate'), [
                'partnerReferenceNo' => $reference,
                'amount' => Money::idr($invoice->outstanding_amount),
                'merchantId' => $this->configuration->merchantId(),
                'terminalId' => $this->configuration->terminalId(),
            ], $reference);
            return BriQrisTransaction::create(['invoice_id' => $invoice->id, 'partner_reference' => $reference, 'provider_reference' => $response->json('referenceNo'), 'qr_content' => $response->json('qrContent'), 'amount' => $invoice->outstanding_amount, 'expires_at' => $expiresAt, 'status' => 'pending']);
        });
    }

    /** @return array<string, mixed> */
    public function inquiry(BriQrisTransaction $transaction): array
    {
        $transaction = BriQrisTransaction::query()->findOrFail($transaction->id);
        if (! $transaction->provider_reference) throw ValidationException::withMessages(['qris' => 'Referensi provider QRIS belum tersedia.']);

        $response = $this->client->post((string) $this->configuration->path('qris_inquiry'), [
            'originalReferenceNo' => $transaction->provider_reference,
            'serviceCode' => $this->configuration->serviceCode('qris'),
            'additionalInfo' => ['terminalId' => $this->configuration->terminalId()],
        ])->json();

        app(BriPaymentNotificationService::class)->qris(array_merge($response, [
            'originalReferenceNo' => $transaction->provider_reference,
            'originalPartnerReferenceNo' => $transaction->partner_reference,
        ]));

        return [
            'latestTransactionStatus' => data_get($response, 'latestTransactionStatus'),
            'paidTime' => data_get($response, 'paidTime'),
            'amount' => data_get($response, 'amount'),
            'terminalId' => data_get($response, 'terminalId'),
            'additionalInfo' => collect((array) data_get($response, 'additionalInfo', []))->only([
                'customerName', 'customerNumber', 'invoiceNumber', 'issuerName', 'issuerRrn', 'mpan',
            ])->all(),
        ];
    }

    private function numericReference(): string
    {
        return now()->format('YmdHisv').str_pad((string) random_int(0, 999999999999999999), 18, '0', STR_PAD_LEFT);
    }
}
