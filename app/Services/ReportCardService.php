<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssessmentComponent;
use App\Models\BtaqJournalStudent;
use App\Models\ReportCard;
use App\Models\ReportCardBtaq;
use App\Models\ReportCardStatusHistory;
use App\Models\ReportCardSubject;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportCardService
{
    public function __construct(private AssessmentService $assessmentService) {}

    public function generate(StudentEnrollment $enrollment, int $semesterId): ReportCard
    {
        return DB::transaction(function () use ($enrollment, $semesterId): ReportCard {
            $card = ReportCard::firstOrCreate([
                'student_id' => $enrollment->student_id,
                'academic_year_id' => $enrollment->academic_year_id,
                'semester_id' => $semesterId,
            ], [
                'student_enrollment_id' => $enrollment->id,
                'classroom_id' => $enrollment->classroom_id,
                'homeroom_teacher_id' => $enrollment->classroom?->homeroom_teacher_id,
                'document_number' => 'RAPOR-'.$enrollment->student_id.'-'.$semesterId,
                'status' => 'draft',
                'generated_at' => now(),
            ]);
            if ($card->status === 'locked') { throw ValidationException::withMessages(['status' => 'Rapor terkunci tidak dapat digenerate ulang.']); }
            $card->forceFill($this->attendanceSnapshot($enrollment, $semesterId) + ['generated_at' => now()])->save();
            ReportCardSubject::where('report_card_id', $card->id)->delete();
            $subjectIds = AssessmentComponent::where('classroom_id', $enrollment->classroom_id)->where('semester_id', $semesterId)->pluck('subject_id')->unique()->values();
            foreach ($subjectIds as $i => $subjectId) {
                $subject = Subject::find($subjectId);
                $result = $this->assessmentService->finalScore($enrollment->student_id, $enrollment->classroom_id, $subjectId, $semesterId);
                if ($result['complete']) {
                    ReportCardSubject::create([
                        'report_card_id' => $card->id,
                        'subject_id' => $subjectId,
                        'final_score' => $result['score'],
                        'predicate' => $result['predicate'],
                        'minimum_passing_grade' => $subject?->minimum_passing_grade,
                        'achievement_description' => 'Capaian '.$subject?->name.' bernilai '.$result['score'].'.',
                        'sequence' => $i + 1,
                    ]);
                }
            }
            $lastBtaq = BtaqJournalStudent::query()->where('student_id', $enrollment->student_id)->latest()->first();
            ReportCardBtaq::updateOrCreate(['report_card_id' => $card->id], [
                'final_score' => $lastBtaq?->reading_score,
                'predicate' => $this->assessmentService->predicate($lastBtaq?->reading_score ? (float) $lastBtaq->reading_score : null),
                'achievement_description' => $lastBtaq?->achievement_notes,
                'development_notes' => $lastBtaq?->follow_up,
                'needs_guidance' => $lastBtaq?->progress_status === 'needs_guidance',
            ]);
            activity('report-card')->performedOn($card)->causedBy(auth()->user())->event('report-card.generated')->log('Rapor digenerate');
            return $card;
        });
    }

    public function transition(ReportCard $card, string $status, ?string $reason = null): void
    {
        DB::transaction(function () use ($card, $status, $reason): void {
            if ($card->status === 'locked' && $status !== 'reopened') { throw ValidationException::withMessages(['status' => 'Rapor terkunci.']); }
            if ($status === 'reopened' && blank($reason)) { throw ValidationException::withMessages(['reason' => 'Alasan reopen wajib diisi.']); }
            $from = $card->status;
            $updates = ['status' => $status];
            if ($status === 'submitted') { $updates['submitted_at'] = now(); }
            if ($status === 'approved') { $updates['approved_at'] = now(); $updates['approved_by'] = auth()->id(); }
            if ($status === 'locked') { $updates['locked_at'] = now(); }
            if ($status === 'reopened') { $updates['reopened_at'] = now(); $updates['reopened_by'] = auth()->id(); }
            $card->update($updates);
            ReportCardStatusHistory::create(['report_card_id' => $card->id, 'from_status' => $from, 'to_status' => $status, 'reason' => $reason, 'changed_by' => auth()->id()]);
            activity('report-card')->performedOn($card)->causedBy(auth()->user())->event('report-card.status')->log('Status rapor diperbarui');
        });
    }

    private function attendanceSnapshot(StudentEnrollment $enrollment, int $semesterId): array
    {
        $q = StudentAttendance::where('student_id', $enrollment->student_id)->where('academic_year_id', $enrollment->academic_year_id);
        return ['sick_count'=>(clone $q)->where('status','sick')->count(), 'permission_count'=>(clone $q)->where('status','permission')->count(), 'alpha_count'=>(clone $q)->where('status','alpha')->count(), 'late_count'=>(clone $q)->where('status','late')->count()];
    }
}
