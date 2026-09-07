<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Bank\BankTransaction;
use App\Models\Finance\StudentVirtualAccount;
use App\Models\Finance\BriQrisTransaction;
use App\Models\Finance\StudentPayment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BriPaymentNotificationService
{
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
        $status = (string) ($payload['latestTransactionStatus'] ?? '');
        if ($partnerReference === '' || $providerReference === '' || ! in_array($status, ['00','01','02','03','04','05','06','07'], true)) throw ValidationException::withMessages(['callback' => 'Referensi atau status callback QRIS tidak valid.']);
        if (! hash_equals('IDR', (string) data_get($payload, 'amount.currency'))) throw ValidationException::withMessages(['callback' => 'Mata uang callback QRIS harus IDR.']);

        return DB::transaction(function () use ($payload, $partnerReference, $providerReference, $amount, $status): BankTransaction {
            $qris = BriQrisTransaction::query()->where('provider_reference', $providerReference)->lockForUpdate()->firstOrFail();
            $validPartnerReference = hash_equals($qris->partner_reference, $partnerReference)
                || (strlen($partnerReference) === 6 && hash_equals(substr($qris->partner_reference, -6), $partnerReference));
            if (! $validPartnerReference) throw ValidationException::withMessages(['callback' => 'Partner reference QRIS tidak cocok.']);

            $transaction = BankTransaction::query()->where('provider', 'BRI')->where('provider_reference', $providerReference)->lockForUpdate()->first();
            $transaction ??= BankTransaction::create(['provider' => 'BRI', 'external_id' => $qris->partner_reference, 'provider_reference' => $providerReference, 'partner_reference' => $qris->partner_reference, 'transaction_type' => 'qris_payment', 'amount' => $amount, 'status' => 'pending', 'occurred_at' => $payload['paidTime'] ?? $payload['transactionDate'] ?? now(), 'request_reference' => $qris->invoice->invoice_number, 'raw_payload' => $payload]);
            $transaction->update(['raw_payload' => $payload, 'amount' => $amount]);
            if (bccomp($amount, (string) $qris->amount, 2) !== 0) {
                $transaction->update(['status' => 'needs_review']);
                return $transaction;
            }
            $mappedStatus = match ($status) { '00' => 'succeeded', '01', '02', '03' => 'pending', '04' => 'refunded', '05' => 'cancelled', '06' => 'failed', '07' => 'not_found' };
            $qris->update(['status' => $mappedStatus]);
            if ($status !== '00') { $transaction->update(['status' => $mappedStatus]); return $transaction->refresh(); }

            $existingPayment = StudentPayment::query()->where('bank_reference', $providerReference)->first();
            if ($existingPayment) { $transaction->update(['status' => 'matched', 'reconciled_at' => $transaction->reconciled_at ?? now()]); return $transaction->refresh(); }
            try {
                $invoice = $qris->invoice;
                app(PaymentService::class)->record($invoice, ['amount' => $amount, 'paid_at' => $transaction->occurred_at, 'payment_method' => 'bri_qris', 'bank_reference' => $providerReference, 'source' => 'bri_callback'], null);
                $transaction->update(['status' => 'matched', 'reconciled_at' => now()]);
                $qris->update(['status' => 'succeeded']);
            } catch (ValidationException) {
                $transaction->update(['status' => 'needs_review']);
            }
            return $transaction->refresh();
        });
    }
}
