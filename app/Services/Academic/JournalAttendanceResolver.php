<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{ClassroomMembership, StudentAttendance, TeachingJournal, User};
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class JournalAttendanceResolver
{
    public function isOpeningLesson(?string $lessonNumber): bool
    {
        preg_match_all('/\d+/', (string) $lessonNumber, $matches);

        return array_slice(array_map('intval', $matches[0]), 0, 2) === [1, 2];
    }

    public function members(int $classroomId, CarbonInterface|string $date): Collection
    {
        return ClassroomMembership::query()
            ->where('classroom_id', $classroomId)
            ->whereDate('joined_at', '<=', $date)
            ->where(fn ($query) => $query->whereNull('left_at')->orWhereDate('left_at', '>=', $date))
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter()
            ->sortBy('full_name')
            ->values();
    }

    public function daily(int $classroomId, CarbonInterface|string $date): Collection
    {
        return StudentAttendance::query()
            ->where('classroom_id', $classroomId)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');
    }

    public function assertCompleteRoster(Collection $members, Collection $rows): void
    {
        $expected = $members->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $submitted = $rows->pluck('student_id')->map(fn ($id) => (int) $id)->unique()->sort()->values();
        if ($expected->all() !== $submitted->all() || $rows->count() !== $submitted->count()) {
            throw ValidationException::withMessages(['attendances' => 'Absensi wajib diisi satu kali untuk seluruh siswa yang terdaftar pada rombel dan tanggal tersebut.']);
        }
    }

    public function writeSnapshots(TeachingJournal $journal, Collection $members, Collection $daily, array $corrections, User $actor): void
    {
        $existing = $journal->attendances()->get()->keyBy('student_id');
        $corrections = collect($corrections)->keyBy(fn ($row) => (int) $row['student_id']);

        foreach ($members as $student) {
            $old = $existing->get($student->id);
            $correction = $corrections->get($student->id);
            if ($correction) {
                $values = ['status' => $correction['status'], 'source' => 'correction', 'notes' => $correction['reason'], 'correction_reason' => $correction['reason'], 'corrected_by' => $actor->id, 'corrected_at' => now()];
            } elseif (in_array($old?->source, ['correction', 'legacy'], true)) {
                continue;
            } elseif ($record = $daily->get($student->id)) {
                $values = ['status' => $record->status->value, 'source' => 'daily', 'notes' => $record->notes, 'correction_reason' => null, 'corrected_by' => null, 'corrected_at' => null];
            } else {
                $values = ['status' => 'pending', 'source' => 'pending', 'notes' => null, 'correction_reason' => null, 'corrected_by' => null, 'corrected_at' => null];
            }
            $journal->attendances()->updateOrCreate(['student_id' => $student->id], $values);
        }
        $journal->attendances()->whereNotIn('student_id', $members->pluck('id'))->where('source', '!=', 'legacy')->delete();
    }

    public function syncFollowingJournals(int $classroomId, CarbonInterface|string $date, Collection $members, Collection $daily, User $actor): void
    {
        TeachingJournal::query()->where('classroom_id', $classroomId)->whereDate('journal_date', $date)->lockForUpdate()->get()
            ->filter(fn (TeachingJournal $journal) => ! $this->isOpeningLesson($journal->lesson_number))
            ->each(fn (TeachingJournal $journal) => $this->writeSnapshots($journal, $members, $daily, [], $actor));
    }
}
