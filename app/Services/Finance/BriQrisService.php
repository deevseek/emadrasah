<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\BriQrisTransaction;
use App\Models\Finance\StudentInvoice;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            $reference = str_replace('-', '', (string) Str::uuid());
            $expiresAt = now()->addMinutes(15);
            $response = $this->client->post((string) $this->configuration->path('qris_generate'), [
                'partnerReferenceNo' => $reference,
                'amount' => Money::idr($invoice->outstanding_amount),
                'merchantId' => $this->configuration->merchantId(),
                'terminalId' => $this->configuration->terminalId(),
                'validityPeriod' => $expiresAt->format('Y-m-d\\TH:i:sP'),
                'additionalInfo' => ['invoiceNumber' => $invoice->invoice_number],
            ], $reference);
            return BriQrisTransaction::create(['invoice_id' => $invoice->id, 'partner_reference' => $reference, 'provider_reference' => $response->json('referenceNo'), 'qr_content' => $response->json('qrContent'), 'amount' => $invoice->outstanding_amount, 'expires_at' => $expiresAt, 'status' => 'pending']);
        });
    }
}
