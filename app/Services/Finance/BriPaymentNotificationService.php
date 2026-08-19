<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Bank\BankTransaction;
use App\Models\Finance\StudentVirtualAccount;
use App\Models\Finance\BriQrisTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BriPaymentNotificationService
{
    public function __construct(private BriConfigurationService $configuration) {}

    /** @param array<string, mixed> $payload */
    public function briva(array $payload): BankTransaction
    {
        $vaNo = (string) data_get($payload, 'virtualAccountData.virtualAccountNo');
        $reference = (string) data_get($payload, 'virtualAccountData.referenceNo');
        $partnerReference = (string) data_get($payload, 'virtualAccountData.partnerReferenceNo');
        $amount = Money::decimal((string) data_get($payload, 'virtualAccountData.paidAmount.value'));
        if ($vaNo === '' || $reference === '' || $partnerReference === '') throw ValidationException::withMessages(['callback' => 'Referensi callback BRIVA tidak lengkap.']);

        return DB::transaction(function () use ($payload, $vaNo, $reference, $partnerReference, $amount): BankTransaction {
            $existing = BankTransaction::query()->where('provider', 'BRI')->where('provider_reference', $reference)->first();
            if ($existing) return $existing;
            $va = StudentVirtualAccount::query()->where('provider', 'BRI')->where('virtual_account_number', $vaNo)->firstOrFail();
            $transaction = BankTransaction::create([
                'provider' => 'BRI', 'external_id' => $partnerReference, 'provider_reference' => $reference,
                'partner_reference' => $partnerReference, 'virtual_account_number' => $vaNo,
                'transaction_type' => 'briva_payment', 'amount' => $amount, 'status' => 'unmatched',
                'occurred_at' => data_get($payload, 'virtualAccountData.trxDateTime'), 'request_reference' => $vaNo,
                'raw_payload' => $payload,
            ]);
            try {
                app(BriReconciliationService::class)->reconcile($transaction, null);
            } catch (ValidationException) {
                $transaction->update(['status' => 'needs_review']);
            }
            return $transaction->refresh();
        });
    }

    /** @param array<string, mixed> $payload */
    public function qris(array $payload): BankTransaction
    {
        $partnerReference = (string) ($payload['originalPartnerReferenceNo'] ?? '');
        $providerReference = (string) ($payload['originalReferenceNo'] ?? '');
        $amount = Money::decimal((string) data_get($payload, 'amount.value'));
        if ($partnerReference === '' || $providerReference === '') throw ValidationException::withMessages(['callback' => 'Referensi callback QRIS tidak lengkap.']);

        return DB::transaction(function () use ($payload, $partnerReference, $providerReference, $amount): BankTransaction {
            $existing = BankTransaction::query()->where('provider', 'BRI')->where('provider_reference', $providerReference)->first();
            if ($existing) return $existing;
            $qris = BriQrisTransaction::query()->where('partner_reference', $partnerReference)->lockForUpdate()->firstOrFail();
            if (! hash_equals((string) $this->configuration->merchantId(), (string) ($payload['merchantId'] ?? '')) ||
                ! hash_equals((string) $this->configuration->terminalId(), (string) ($payload['terminalId'] ?? ''))) {
                throw ValidationException::withMessages(['callback' => 'Merchant atau terminal QRIS tidak valid.']);
            }
            $transaction = BankTransaction::create(['provider' => 'BRI', 'external_id' => $partnerReference, 'provider_reference' => $providerReference, 'partner_reference' => $partnerReference, 'transaction_type' => 'qris_payment', 'amount' => $amount, 'status' => 'unmatched', 'occurred_at' => $payload['transactionDate'] ?? now(), 'request_reference' => $qris->invoice->invoice_number, 'raw_payload' => $payload]);
            if (bccomp($amount, (string) $qris->amount, 2) !== 0) {
                $transaction->update(['status' => 'needs_review']);
                return $transaction;
            }
            try {
                $invoice = $qris->invoice;
                app(PaymentService::class)->record($invoice, ['amount' => $amount, 'paid_at' => $transaction->occurred_at, 'payment_method' => 'bri_qris', 'bank_reference' => $providerReference, 'source' => 'bri_callback'], null);
                $transaction->update(['status' => 'matched', 'reconciled_at' => now()]);
                $qris->update(['status' => 'succeeded', 'provider_reference' => $providerReference]);
            } catch (ValidationException) {
                $transaction->update(['status' => 'needs_review']);
            }
            return $transaction->refresh();
        });
    }
}
