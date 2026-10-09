<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalAttendanceResolver
{
    public function members(array $context, ?int $studentId = null): Collection
    {
        return ClassroomMembership::with('student')->where('classroom_id', $context['classroom_id'])
            ->where('academic_year_id', $context['academic_year_id'])->where('status', 'active')
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->whereDate('joined_at', '<=', $context['journal_date'])
            ->where(fn ($q) => $q->whereNull('left_at')->orWhereDate('left_at', '>=', $context['journal_date']))
            ->get()->unique('student_id');
    }

    public function resolve(array $context, ?int $studentId = null, ?Collection $members = null): Collection
    {
        $year = AcademicYear::find($context['academic_year_id']);
        $semester = Semester::where('academic_year_id', $context['academic_year_id'])->find($context['semester_id']);
        $room = Classroom::where('academic_year_id', $context['academic_year_id'])->where('is_active', true)->find($context['classroom_id']);
        $day = Carbon::parse($context['journal_date'])->toDateString();
        if (! $year || ! $semester || ! $room || $day < $year->starts_at->toDateString() || $day > $year->ends_at->toDateString() || $day < $semester->starts_at->toDateString() || $day > $semester->ends_at->toDateString()) {
            throw ValidationException::withMessages(['journal_date' => 'Tanggal dan rombel jurnal harus sesuai tahun ajaran dan semester yang dipilih.']);
        }
        $date = Carbon::parse($context['journal_date'], config('app.timezone', 'Asia/Jakarta'));
        $daily = StudentAttendance::where('classroom_id', $context['classroom_id'])
            ->where('academic_year_id', $context['academic_year_id'])->where('semester_id', $context['semester_id'])
            ->whereDate('attendance_date', $date)
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->when(DB::transactionLevel() > 0, fn ($q) => $q->lockForUpdate())
            ->get()->keyBy('student_id');
        $daySchedules = LessonSchedule::where('classroom_id', $context['classroom_id'])
            ->where('academic_year_id', $context['academic_year_id'])->where('semester_id', $context['semester_id'])
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('active', true)->get();
        $schedules = $daySchedules->where('academic_subject_id', $context['academic_subject_id']);
        $safeStart = ($schedules->isNotEmpty() ? $schedules : $daySchedules)->min('start_time') ?? '07:00:00';

        return ($members ?? $this->members($context, $studentId))->mapWithKeys(function ($member) use ($daily, $safeStart, $date): array {
            $record = $daily->get($member->student_id);
            $status = $record?->status->value ?? 'pending';
            $source = $record?->source->value;
            if ($source === 'rfid' && $record->scanned_at) {
                $arrival = $record->scanned_at->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
                // With repeated sessions, arrival must precede the earliest possible
                // lesson. With no schedule at all, use a conservative 07:00 cutoff.
                $start = $date->copy()->setTimeFromTimeString($safeStart);
                if ($arrival->gt($start)) {
                    $status = 'pending';
                }
            }

            return [$member->student_id => [
                'student_id' => $member->student_id, 'name' => $member->student->full_name,
                'status' => $status, 'notes' => $record?->notes, 'origin' => 'daily',
                'daily_source' => $source, 'arrival_at' => $record?->scanned_at?->format('Y-m-d H:i:s'),
            ]];
        });
    }

    public function synchronize(StudentAttendance $attendance): void
    {
        DB::transaction(function () use ($attendance): void {
            TeachingJournal::where('classroom_id', $attendance->classroom_id)
                ->where('academic_year_id', $attendance->academic_year_id)->where('semester_id', $attendance->semester_id)
                ->whereDate('journal_date', $attendance->attendance_date)->orderBy('id')->get()
                ->each(function ($journal) use ($attendance): void {
                    $journal = TeachingJournal::whereKey($journal->id)->lockForUpdate()->firstOrFail();
                    $resolved = $this->resolve($journal->getAttributes(), $attendance->student_id)->get($attendance->student_id);
                    if ($resolved) {
                        $journal->attendances()->where('student_id', $attendance->student_id)->where('origin', 'daily')
                            ->update(collect($resolved)->except(['student_id', 'name'])->all());
                    }
                });
        });
    }
}
