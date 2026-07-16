<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\TeachingJournalStatus;
use App\Models\StudentAttendance;
use App\Models\TeachingAssignment;
use App\Models\TeachingJournal;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TeachingJournalService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function save(TeachingAssignment $assignment, array $data, array $students = []): TeachingJournal
    {
        return DB::transaction(function () use ($assignment, $data, $students): TeachingJournal {
            if (auth()->user()?->employee !== null && auth()->user()->employee->id !== $assignment->employee_id) {
                throw ValidationException::withMessages(['teaching_assignment_id' => 'Guru hanya dapat memilih penugasan miliknya.']);
            }

            $journal = isset($data['id']) ? TeachingJournal::query()->findOrFail($data['id']) : new TeachingJournal();

            if ($journal->exists && $journal->status === TeachingJournalStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Jurnal yang sudah dikirim tidak dapat diedit.']);
            }

            $journal->fill($data + [
                'teaching_assignment_id' => $assignment->id,
                'employee_id' => $assignment->employee_id,
                'subject_id' => $assignment->subject_id,
                'classroom_id' => $assignment->classroom_id,
                'academic_year_id' => $assignment->academic_year_id,
                'semester_id' => $assignment->semester_id,
            ]);
            $journal->save();

            $this->syncStudents($journal, $students);
            $this->logger->log('teaching-journal.saved', $journal, [], $journal->toArray());

            return $journal;
        });
    }

    public function submit(TeachingJournal $journal): void
    {
        if (! in_array($journal->status, [TeachingJournalStatus::Draft, TeachingJournalStatus::Rejected], true)) {
            throw ValidationException::withMessages(['status' => 'Jurnal tidak dapat dikirim pada status saat ini.']);
        }

        $journal->update(['status' => TeachingJournalStatus::Submitted, 'submitted_at' => now()]);
        $this->logger->log('teaching-journal.submitted', $journal);
    }

    public function verify(TeachingJournal $journal): void
    {
        $journal->update([
            'status' => TeachingJournalStatus::Verified,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);
        $this->logger->log('teaching-journal.verified', $journal);
    }

    public function reject(TeachingJournal $journal, string $reason): void
    {
        $journal->update([
            'status' => TeachingJournalStatus::Rejected,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);
        $this->logger->log('teaching-journal.rejected', $journal, [], [], $reason);
    }

    private function syncStudents(TeachingJournal $journal, array $students): void
    {
        if ($students !== []) {
            $journal->students()->sync(collect($students)->mapWithKeys(fn (array $row, int|string $id): array => [
                $id => ['status' => $row['status'], 'notes' => $row['notes'] ?? null],
            ])->all());

            return;
        }

        $attendances = StudentAttendance::query()
            ->where('classroom_id', $journal->classroom_id)
            ->whereDate('attendance_date', $journal->journal_date)
            ->get();

        if ($attendances->isNotEmpty()) {
            $journal->students()->sync($attendances->mapWithKeys(fn (StudentAttendance $attendance): array => [
                $attendance->student_id => ['status' => $attendance->status->value, 'notes' => $attendance->notes],
            ])->all());
        }
    }
}
