<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Enums\Pmbm\{ApplicantStatus, PaymentStage, PaymentStatus};
use App\Models\Pmbm\{PmbmApplicant, PmbmDocumentRequirement, PmbmSetting};
use App\Models\User;

class PmbmReadinessService
{
    public function __construct(private readonly PmbmRegistrationService $registration) {}

    /** @return array{documents_satisfied:bool,payment_satisfied:bool} */
    public function reconcile(PmbmApplicant $applicant, ?User $actor = null): array
    {
        $setting = PmbmSetting::query()->where('academic_year_id', $applicant->academic_year_id)->firstOrFail();
        $requirements = PmbmDocumentRequirement::query()
            ->where('is_active', true)->where('is_required', true)
            ->where(fn ($query) => $query->where('setting_id', $setting->id)->orWhereNull('setting_id'))
            ->whereIn('channel', ['both', $applicant->registration_source])
            ->pluck('code');
        $valid = $applicant->currentDocuments()->where('status', 'valid')->pluck('document_type');
        $documentsSatisfied = $requirements->diff($valid)->isEmpty();

        $total = collect($setting->fee_items ?? [])->sum(fn (array $item): float => (float) ($item[$applicant->gender] ?? $item['amount'] ?? 0));
        $minimum = round($total * $setting->minimum_initial_payment_percent / 100, 2);
        $verified = (float) $applicant->payments()->where('payment_stage', PaymentStage::Initial)->where('status', PaymentStatus::Verified)->sum('amount');
        $paymentSatisfied = $verified >= $minimum;

        if (in_array($applicant->status, [ApplicantStatus::Submitted, ApplicantStatus::DocumentVerification, ApplicantStatus::Verified, ApplicantStatus::InitialPaymentPending, ApplicantStatus::InitialPaymentVerified], true)) {
            $target = $documentsSatisfied && $paymentSatisfied
                ? ApplicantStatus::InitialPaymentVerified
                : ($documentsSatisfied ? ApplicantStatus::InitialPaymentPending : ApplicantStatus::DocumentVerification);
            $this->registration->transition($applicant, $target, $actor, 'Prasyarat dokumen dan pembayaran awal direkonsiliasi.');
        }

        return ['documents_satisfied' => $documentsSatisfied, 'payment_satisfied' => $paymentSatisfied];
    }
}
