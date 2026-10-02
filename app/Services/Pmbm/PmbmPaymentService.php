<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Enums\Pmbm\{ApplicantStatus, PaymentStage, PaymentStatus};
use App\Models\Pmbm\{PmbmApplicant, PmbmPayment, PmbmSetting};
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PmbmPaymentService
{
    public function __construct(private readonly PmbmRegistrationService $registration) {}

    /** @return array{total:float,minimum_percent:int,minimum_initial:float,verified:float,remaining:float,initial_verified:float,initial_satisfied:bool} */
    public function summary(PmbmApplicant $applicant): array
    {
        $setting = PmbmSetting::query()->where('academic_year_id', $applicant->academic_year_id)->first();
        $total = collect($setting?->fee_items ?? [])->sum(function (array $item) use ($applicant): float {
            $value = $item[$applicant->gender] ?? $item['amount'] ?? 0;

            return max(0, (float) $value);
        });
        $percent = min(100, max(0, (int) ($setting?->minimum_initial_payment_percent ?? 50)));
        $minimum = round($total * $percent / 100, 2);
        $verified = (float) $applicant->payments()->where('status', PaymentStatus::Verified)->sum('amount');
        $initial = (float) $applicant->payments()->where('payment_stage', PaymentStage::Initial)->where('status', PaymentStatus::Verified)->sum('amount');

        return ['total' => $total, 'minimum_percent' => $percent, 'minimum_initial' => $minimum, 'verified' => $verified, 'remaining' => max(0, $total - $verified), 'initial_verified' => $initial, 'initial_satisfied' => $initial >= $minimum];
    }

    public function record(PmbmApplicant $applicant, array $data, ?User $actor = null): PmbmPayment
    {
        return DB::transaction(function () use ($applicant, $data, $actor): PmbmPayment {
            $proof = $data['proof'] ?? null;
            unset($data['proof']);
            if ($proof instanceof UploadedFile) {
                $data['proof_path'] = $proof->store("pmbm/{$applicant->id}/payments", 'local');
            }
            $payment = $applicant->payments()->create($data + ['status' => PaymentStatus::Pending, 'received_by' => $actor?->id]);
            activity('pmbm')->causedBy($actor)->performedOn($applicant)->withProperties(['payment_id' => $payment->id, 'amount' => $payment->amount])->log('Pembayaran PMBM dicatat.');

            return $payment;
        });
    }

    public function verify(PmbmApplicant $applicant, PmbmPayment $payment, bool $approved, ?string $note, User $actor): void
    {
        DB::transaction(function () use ($applicant, $payment, $approved, $note, $actor): void {
            $payment = PmbmPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->applicant_id !== $applicant->id) abort(404);
            if ($payment->status !== PaymentStatus::Pending) throw ValidationException::withMessages(['payment' => 'Transaksi ini sudah diputuskan dan tidak dapat diverifikasi ulang.']);
            $payment->update(['status' => $approved ? PaymentStatus::Verified : PaymentStatus::Rejected, 'verification_note' => $note, 'verified_by' => $actor->id, 'verified_at' => now()]);
            $applicant->refresh();
            $summary = $this->summary($applicant);
            if ($approved && $summary['initial_satisfied'] && $applicant->status === ApplicantStatus::InitialPaymentPending) {
                $this->registration->transition($applicant, ApplicantStatus::InitialPaymentVerified, $actor, 'Pembayaran awal minimum telah terverifikasi.');
            }
            if ($approved && $payment->payment_stage === PaymentStage::Registration && in_array($applicant->status, [ApplicantStatus::Accepted, ApplicantStatus::RegistrationPaymentPending, ApplicantStatus::RegistrationPaymentPartial], true)) {
                if ($applicant->status === ApplicantStatus::Accepted) $this->registration->transition($applicant, ApplicantStatus::RegistrationPaymentPending, $actor, 'Proses administrasi daftar ulang dimulai.');
                if ($applicant->status === ApplicantStatus::RegistrationPaymentPending) $this->registration->transition($applicant, ApplicantStatus::RegistrationPaymentPartial, $actor, 'Pembayaran daftar ulang telah diverifikasi.');
                $setting = PmbmSetting::query()->where('academic_year_id', $applicant->academic_year_id)->first();
                if (! $setting?->require_payment_before_registration_complete || $summary['remaining'] <= 0) {
                    $this->registration->transition($applicant, ApplicantStatus::Registered, $actor, 'Seluruh syarat pembayaran daftar ulang telah terpenuhi.');
                }
            }
            activity('pmbm')->causedBy($actor)->performedOn($applicant)->withProperties(['payment_id' => $payment->id, 'status' => $payment->status->value])->log('Pembayaran PMBM diverifikasi.');
        });
    }

    public function assertInitialPaymentSatisfied(PmbmApplicant $applicant): void
    {
        if (! $this->summary($applicant)['initial_satisfied']) {
            throw ValidationException::withMessages(['payment' => 'Pembayaran awal terverifikasi belum mencapai minimum yang ditetapkan.']);
        }
    }
}
