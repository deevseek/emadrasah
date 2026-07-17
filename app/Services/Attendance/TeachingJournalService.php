<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\TeachingJournalStatus;
use App\Models\LessonSchedule;
use App\Models\TeachingJournal;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TeachingJournalService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function createFromSchedule(LessonSchedule $schedule, array $data): TeachingJournal
    {
        return DB::transaction(function () use ($schedule, $data): TeachingJournal {
            $this->assertScheduleAllowed($schedule, $data['journal_date']);
            if (TeachingJournal::where('lesson_schedule_id', $schedule->id)->whereDate('journal_date', $data['journal_date'])->exists()) {
                throw ValidationException::withMessages(['lesson_schedule_id' => 'Jurnal untuk jadwal dan tanggal ini sudah ada.']);
            }
            $meeting = $this->nextMeetingNumber($schedule->teaching_assignment_id);
            $journal = TeachingJournal::create($this->payload($schedule, $data, $meeting) + ['created_by'=>auth()->id(), 'updated_by'=>auth()->id()]);
            $this->logger->log('teaching-journal.created', $journal, [], ['status'=>$journal->status->value]);
            if ($journal->status === TeachingJournalStatus::Submitted) { $this->markSubmitted($journal); }
            return $journal;
        });
    }

    public function update(TeachingJournal $journal, array $data): TeachingJournal
    {
        return DB::transaction(function () use ($journal, $data): TeachingJournal {
            $locked = TeachingJournal::lockForUpdate()->findOrFail($journal->id);
            if (! $locked->isEditableByTeacher()) { throw ValidationException::withMessages(['status'=>'Jurnal yang sudah dikirim atau terverifikasi tidak dapat diedit.']); }
            $schedule = LessonSchedule::findOrFail($locked->lesson_schedule_id);
            $old = ['status'=>$locked->status->value];
            $locked->fill($this->contentPayload($data) + ['status'=>$data['status'], 'updated_by'=>auth()->id()]);
            $locked->save();
            $this->logger->log('teaching-journal.updated', $locked, $old, ['status'=>$locked->status->value]);
            if ($locked->status === TeachingJournalStatus::Submitted) { $this->markSubmitted($locked); }
            return $locked;
        });
    }

    public function submit(TeachingJournal $journal): void { DB::transaction(fn () => $this->markSubmitted(TeachingJournal::lockForUpdate()->findOrFail($journal->id))); }
    public function verify(TeachingJournal $journal, ?string $notes = null): void
    {
        DB::transaction(function () use ($journal, $notes): void {
            $locked = TeachingJournal::lockForUpdate()->findOrFail($journal->id);
            if ($locked->status !== TeachingJournalStatus::Submitted) { throw ValidationException::withMessages(['status'=>'Hanya jurnal berstatus dikirim yang dapat diverifikasi.']); }
            $old = ['status'=>$locked->status->value];
            $locked->update(['status'=>TeachingJournalStatus::Verified,'verified_by'=>auth()->id(),'verified_at'=>now(),'verification_notes'=>$notes,'rejection_reason'=>null]);
            $this->logger->log('teaching-journal.verified', $locked, $old, ['status'=>'verified']);
        });
    }
    public function reject(TeachingJournal $journal, string $reason): void
    {
        DB::transaction(function () use ($journal, $reason): void {
            $locked = TeachingJournal::lockForUpdate()->findOrFail($journal->id);
            if ($locked->status !== TeachingJournalStatus::Submitted) { throw ValidationException::withMessages(['status'=>'Hanya jurnal berstatus dikirim yang dapat diminta perbaikan.']); }
            $old = ['status'=>$locked->status->value];
            $locked->update(['status'=>TeachingJournalStatus::Rejected,'rejected_by'=>auth()->id(),'rejected_at'=>now(),'rejection_reason'=>$reason]);
            $this->logger->log('teaching-journal.rejected', $locked, $old, ['status'=>'rejected'], 'Jurnal diminta perbaikan');
        });
    }
    public function nextMeetingNumber(int $assignmentId): int { return (int) TeachingJournal::where('teaching_assignment_id',$assignmentId)->max('meeting_number') + 1; }
    private function markSubmitted(TeachingJournal $journal): void
    {
        if (! in_array($journal->status, [TeachingJournalStatus::Draft, TeachingJournalStatus::Rejected, TeachingJournalStatus::Submitted], true)) { throw ValidationException::withMessages(['status'=>'Jurnal tidak dapat dikirim pada status saat ini.']); }
        $old = ['status'=>$journal->status->value];
        $journal->update(['status'=>TeachingJournalStatus::Submitted,'submitted_at'=>$journal->submitted_at ?? now(),'updated_by'=>auth()->id()]);
        $this->logger->log('teaching-journal.submitted', $journal, $old, ['status'=>'submitted']);
    }
    private function assertScheduleAllowed(LessonSchedule $schedule, string $date): void
    {
        if (! $schedule->is_active || ! $schedule->teachingAssignment?->is_active) { throw ValidationException::withMessages(['lesson_schedule_id'=>'Jadwal mengajar tidak aktif.']); }
        if (auth()->user()?->employee && auth()->user()->employee->id !== $schedule->employee_id) { throw ValidationException::withMessages(['lesson_schedule_id'=>'Guru hanya dapat mengisi jurnal dari jadwal miliknya.']); }
        if (now()->parse($date)->isFuture()) { throw ValidationException::withMessages(['journal_date'=>'Tanggal masa depan tidak diperbolehkan.']); }
    }
    private function payload(LessonSchedule $s, array $data, int $meeting): array
    {
        return $this->contentPayload($data) + ['teaching_assignment_id'=>$s->teaching_assignment_id,'lesson_schedule_id'=>$s->id,'employee_id'=>$s->employee_id,'subject_id'=>$s->subject_id,'classroom_id'=>$s->classroom_id,'academic_year_id'=>$s->academic_year_id,'semester_id'=>$s->semester_id,'journal_date'=>$data['journal_date'],'starts_at'=>$s->starts_at,'ends_at'=>$s->ends_at,'scheduled_start_time'=>$s->starts_at,'scheduled_end_time'=>$s->ends_at,'lesson_hours'=>$s->lesson_hours ?? 1,'meeting_number'=>$meeting,'status'=>$data['status']];
    }
    private function contentPayload(array $d): array { return ['learning_topic'=>$d['learning_topic'],'learning_objectives'=>$d['learning_objectives'],'learning_material'=>$d['learning_material'],'material'=>$d['learning_material'],'learning_method'=>$d['learning_method'] ?? null,'method'=>$d['learning_method'] ?? null,'learning_media'=>$d['learning_media'] ?? null,'media'=>$d['learning_media'] ?? null,'learning_activity'=>$d['learning_activity'],'assessment_activity'=>$d['assessment_activity'] ?? null,'assessment'=>$d['assessment_activity'] ?? null,'homework'=>$d['homework'] ?? null,'assignment'=>$d['homework'] ?? null,'teacher_notes'=>$d['teacher_notes'] ?? null,'obstacles'=>$d['obstacles'] ?? null,'follow_up'=>$d['follow_up'] ?? null]; }
}
