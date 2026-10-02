<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Enums\Pmbm\ApplicantStatus;
use App\Mail\PmbmResultMail;
use App\Models\Pmbm\{PmbmApplicant, PmbmChildInterview, PmbmDecision, PmbmFitting, PmbmInterview, PmbmInterviewAnswer, PmbmInterviewQuestion, PmbmObservation, PmbmSetting};
use App\Models\User;
use Illuminate\Support\Facades\{DB, Mail};
use Illuminate\Validation\ValidationException;

class PmbmWorkflowService
{
    public function __construct(private readonly PmbmRegistrationService $registration, private readonly PmbmReadinessService $readiness) {}

    private function setting(PmbmApplicant $applicant): PmbmSetting { return PmbmSetting::where('academic_year_id', $applicant->academic_year_id)->firstOrFail(); }

    public function advanceWorkflow(PmbmApplicant $applicant, ?User $actor = null): void
    {
        $applicant->refresh();
        $setting = $this->setting($applicant);
        if (in_array($applicant->status, [ApplicantStatus::Submitted, ApplicantStatus::DocumentVerification, ApplicantStatus::Verified, ApplicantStatus::InitialPaymentPending, ApplicantStatus::InitialPaymentVerified], true)) {
            $ready = $this->readiness->reconcile($applicant, $actor);
            if (! $ready['documents_satisfied'] || ! $ready['payment_satisfied']) return;
        }
        if ($setting->require_fitting && ! $applicant->fitting?->completed_at) return;
        if ($setting->require_parent_interview && ! $applicant->interview?->completed_at) return;
        if ($setting->require_child_interview && ! $applicant->childInterview?->completed_at) return;
        if ($setting->require_child_observation && ! $applicant->observation?->completed_at) return;
        if (in_array($applicant->status,[ApplicantStatus::InitialPaymentVerified,ApplicantStatus::FittingCompleted,ApplicantStatus::Interviewed,ApplicantStatus::ChildInterviewed,ApplicantStatus::Observed],true)) $this->registration->transition($applicant, ApplicantStatus::DecisionPending, $actor, 'Seluruh tahap penerimaan yang diwajibkan telah selesai.');
    }

    public function scheduleFitting(PmbmApplicant $applicant, array $data, User $actor): PmbmFitting
    {
        return DB::transaction(function () use ($applicant, $data, $actor) {
            $this->assertReady($applicant);
            if (! $this->setting($applicant)->require_fitting) throw ValidationException::withMessages(['status' => 'Fitting tidak diwajibkan pada penerimaan ini.']);
            $fitting = PmbmFitting::updateOrCreate(['applicant_id' => $applicant->id], $data + ['officer_id' => $data['officer_id'] ?? $actor->id, 'attendance_status' => 'scheduled']);
            $this->registration->transition($applicant, ApplicantStatus::FittingScheduled, $actor, 'Fitting seragam dijadwalkan.');
            return $fitting;
        });
    }

    public function completeFitting(PmbmApplicant $applicant, array $data, User $actor): void
    {
        DB::transaction(function () use ($applicant, $data, $actor): void {
            $this->requireStatus($applicant, ApplicantStatus::FittingScheduled);
            $applicant->fitting()->firstOrFail()->update($data + ['attendance_status' => 'present', 'completed_at' => now(), 'completed_by' => $actor->id]);
            $this->registration->transition($applicant, ApplicantStatus::FittingCompleted, $actor, 'Fitting seragam selesai.');
            $this->advanceWorkflow($applicant, $actor);
        });
    }

    public function scheduleInterview(PmbmApplicant $applicant, array $data, User $actor): PmbmInterview
    {
        $this->assertPreviousStages($applicant, 'parent');
        $interview = PmbmInterview::updateOrCreate(['applicant_id' => $applicant->id], $data + ['interviewer_id' => $data['interviewer_id'] ?? $actor->id]);
        $this->registration->transition($applicant, ApplicantStatus::InterviewScheduled, $actor, 'Wawancara orang tua dijadwalkan.');
        return $interview;
    }

    public function completeInterview(PmbmApplicant $applicant, array $data, User $actor): void
    {
        DB::transaction(function () use ($applicant, $data, $actor): void {
            $this->requireStatus($applicant, ApplicantStatus::InterviewScheduled);
            $interview = $applicant->interview()->firstOrFail();
            foreach ($data['answers'] ?? [] as $questionId => $answer) PmbmInterviewAnswer::updateOrCreate(['interview_id' => $interview->id, 'question_id' => $questionId], ['answer' => is_array($answer) ? $answer : [$answer]]);
            $answered = collect($data['answers'] ?? [])->filter(fn ($answer) => collect((array) $answer)->contains(fn ($value) => filled($value)));
            if (PmbmInterviewQuestion::where('active', true)->where('required', true)->whereNotIn('id', $answered->keys())->exists()) throw ValidationException::withMessages(['answers' => 'Semua pertanyaan wajib harus diisi.']);
            $interview->update(['started_at' => $interview->started_at ?? now(), 'completed_at' => now(), 'summary' => $data['summary'] ?? null, 'public_note' => $data['public_note'] ?? null, 'internal_note' => $data['internal_note'] ?? null]);
            $this->registration->transition($applicant, ApplicantStatus::Interviewed, $actor, 'Wawancara orang tua selesai.');
            $this->advanceWorkflow($applicant, $actor);
        });
    }

    public function scheduleChildInterview(PmbmApplicant $applicant, array $data, User $actor): PmbmChildInterview
    {
        $this->assertPreviousStages($applicant, 'child');
        $interview = PmbmChildInterview::updateOrCreate(['applicant_id' => $applicant->id], $data + ['interviewer_id' => $data['interviewer_id'] ?? $actor->id]);
        $this->registration->transition($applicant, ApplicantStatus::ChildInterviewScheduled, $actor, 'Wawancara anak dijadwalkan.');
        return $interview;
    }

    public function completeChildInterview(PmbmApplicant $applicant, array $data, User $actor): void
    {
        $this->requireStatus($applicant, ApplicantStatus::ChildInterviewScheduled);
        $applicant->childInterview()->firstOrFail()->update($data + ['started_at' => $applicant->childInterview?->started_at ?? now(), 'completed_at' => now()]);
        $this->registration->transition($applicant, ApplicantStatus::ChildInterviewed, $actor, 'Wawancara anak selesai.');
        $this->advanceWorkflow($applicant, $actor);
    }

    public function scheduleObservation(PmbmApplicant $applicant, array $data, User $actor): PmbmObservation
    {
        $this->assertPreviousStages($applicant, 'observation');
        $observation = PmbmObservation::updateOrCreate(['applicant_id' => $applicant->id], $data + ['observer_id' => $data['observer_id'] ?? $actor->id]);
        $this->registration->transition($applicant, ApplicantStatus::ObservationScheduled, $actor, 'Observasi anak dijadwalkan.');
        return $observation;
    }

    public function completeObservation(PmbmApplicant $applicant, array $data, User $actor): void
    {
        $this->requireStatus($applicant, ApplicantStatus::ObservationScheduled);
        $applicant->observation()->firstOrFail()->update($data + ['observer_id' => $actor->id, 'completed_at' => now()]);
        $this->registration->transition($applicant, ApplicantStatus::Observed, $actor, 'Observasi anak selesai.');
        $this->advanceWorkflow($applicant, $actor);
    }

    public function recommendDecision(PmbmApplicant $applicant, array $data, User $actor): PmbmDecision
    {
        $this->requireStatus($applicant, ApplicantStatus::DecisionPending);
        $decision = PmbmDecision::updateOrCreate(['applicant_id' => $applicant->id], ['decision' => $data['decision'], 'public_note' => $data['public_note'] ?? null, 'internal_note' => $data['internal_note'] ?? null, 'decided_by' => $actor->id, 'decided_at' => now(), 'finalized_at' => null, 'published_at' => null]);
        if (! $this->setting($applicant)->require_headmaster_approval) $this->approveDecision($applicant, $actor);
        return $decision;
    }

    public function approveDecision(PmbmApplicant $applicant, User $actor): void
    {
        $this->requireStatus($applicant, ApplicantStatus::DecisionPending);
        $decision = $applicant->decision()->lockForUpdate()->firstOrFail();
        if ($decision->finalized_at) return;
        $decision->update(['approved_by' => $actor->id, 'approved_at' => now(), 'finalized_by' => $actor->id, 'finalized_at' => now()]);
    }

    public function publishDecision(PmbmApplicant $applicant, ?User $actor = null): void
    {
        DB::transaction(function () use ($applicant, $actor): void {
            $decision = $applicant->decision()->lockForUpdate()->firstOrFail();
            if (! $decision->finalized_at) throw ValidationException::withMessages(['decision' => 'Keputusan internal belum disahkan.']);
            if ($decision->published_at) return;
            $status = ApplicantStatus::from($decision->decision);
            $updates = ['published_at' => now(), 'published_by' => $actor?->id];
            if ($status === ApplicantStatus::Accepted && ! $decision->acceptance_letter_number) $updates['acceptance_letter_number'] = sprintf('SK/PMBM/%s/%06d', now()->format('Y'), $applicant->id);
            if ($status === ApplicantStatus::Waitlisted && ! $decision->waitlist_order) $updates['waitlist_order'] = (PmbmDecision::where('decision', 'waitlisted')->whereHas('applicant', fn ($query) => $query->where('academic_year_id', $applicant->academic_year_id))->max('waitlist_order') ?? 0) + 1;
            $decision->update($updates);
            $this->registration->transition($applicant, $status, $actor, 'Hasil PMBM dipublikasikan.');
            $this->sendResultMail($applicant, $decision);
        });
    }

    public function promoteWaitlist(PmbmApplicant $applicant, string $reason, User $actor): void
    {
        DB::transaction(function () use ($applicant, $reason, $actor): void {
            $this->requireStatus($applicant, ApplicantStatus::Waitlisted);
            $decision = $applicant->decision()->lockForUpdate()->firstOrFail();
            $decision->update(['decision' => 'accepted', 'acceptance_letter_number' => sprintf('SK/PMBM/%s/%06d', now()->format('Y'), $applicant->id), 'promoted_by' => $actor->id, 'promoted_at' => now(), 'promotion_reason' => $reason, 'result_emailed_at' => null]);
            $this->registration->transition($applicant, ApplicantStatus::Accepted, $actor, 'Daftar cadangan dipromosikan: '.$reason);
            $this->sendResultMail($applicant, $decision);
        });
    }

    private function sendResultMail(PmbmApplicant $applicant, PmbmDecision $decision): void
    {
        if (! $applicant->parent_email || $decision->result_emailed_at) return;
        $decision->update(['result_emailed_at' => now()]);
        DB::afterCommit(fn () => Mail::to($applicant->parent_email)->queue(new PmbmResultMail($applicant->fresh(['decision']))));
    }

    private function assertReady(PmbmApplicant $applicant): void
    {
        $ready = $this->readiness->reconcile($applicant);
        if (! $ready['documents_satisfied'] || ! $ready['payment_satisfied']) throw ValidationException::withMessages(['status' => 'Dokumen wajib dan pembayaran awal minimum harus terpenuhi.']);
    }

    private function assertPreviousStages(PmbmApplicant $applicant, string $stage): void
    {
        $this->assertReady($applicant);
        $setting = $this->setting($applicant);
        if ($setting->require_fitting && ! $applicant->fitting?->completed_at) throw ValidationException::withMessages(['status' => 'Fitting wajib diselesaikan lebih dahulu.']);
        if (in_array($stage, ['child', 'observation'], true) && $setting->require_parent_interview && ! $applicant->interview?->completed_at) throw ValidationException::withMessages(['status' => 'Wawancara orang tua wajib diselesaikan lebih dahulu.']);
        if ($stage === 'observation' && $setting->require_child_interview && ! $applicant->childInterview?->completed_at) throw ValidationException::withMessages(['status' => 'Wawancara anak wajib diselesaikan lebih dahulu.']);
    }

    private function requireStatus(PmbmApplicant $applicant, ApplicantStatus ...$statuses): void
    {
        if (! in_array($applicant->status, $statuses, true)) throw ValidationException::withMessages(['status' => 'Aksi tidak tersedia pada tahap PMBM saat ini.']);
    }
}
