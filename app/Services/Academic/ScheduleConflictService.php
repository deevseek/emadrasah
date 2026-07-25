<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\LessonSchedule;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    public function assertNoConflict(array $data, ?LessonSchedule $ignore = null): void
    {
        $base = LessonSchedule::with(['employee', 'classroom'])->where('is_active', true)
            ->where('semester_id', $data['semester_id'])->where('day_of_week', $data['day_of_week'])
            ->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at']);
        if ($ignore) {
            $base->whereKeyNot($ignore->id);
        }

        $teachers = empty($data['employee_id']) ? collect() : (clone $base)->where('employee_id', $data['employee_id'])->get();
        if ($teacher = $teachers->first(fn (LessonSchedule $existing) => ! $this->belongsToSameSharedSession($data, $existing))) {
            throw ValidationException::withMessages(['teaching_assignment_id' => 'Jadwal tidak dapat disimpan karena '.$teacher->employee?->name.' sudah mengajar '.$teacher->classroom?->name.' pada '.$this->time($teacher).'.']);
        }
        if ($class = (clone $base)->where('classroom_id', $data['classroom_id'])->first()) {
            throw ValidationException::withMessages(['teaching_assignment_id' => 'Jadwal tidak dapat disimpan karena '.$class->classroom?->name.' sudah memiliki pelajaran pada '.$this->time($class).'.']);
        }
        if (filled($data['room'] ?? null)) {
            $rooms = (clone $base)->where('room', $data['room'])->get();
            if ($room = $rooms->first(fn (LessonSchedule $existing) => ! $this->belongsToSameSharedSession($data, $existing))) {
                throw ValidationException::withMessages(['room' => 'Ruangan '.$data['room'].' sudah digunakan pada '.$this->time($room).'.']);
            }
        }
    }

    public function belongsToSameSharedSession(array $incoming, LessonSchedule $existing): bool
    {
        return ! empty($incoming['employee_id'])
            && ! empty($existing->employee_id)
            && filled($incoming['shared_session_code'] ?? null)
            && $incoming['shared_session_code'] === $existing->shared_session_code
            && (int) $incoming['employee_id'] === (int) $existing->employee_id
            && (int) $incoming['subject_id'] === (int) $existing->subject_id
            && (int) $incoming['semester_id'] === (int) $existing->semester_id
            && (string) $incoming['day_of_week'] === $existing->day_of_week->value
            && substr((string) $incoming['starts_at'], 0, 5) === substr((string) $existing->starts_at, 0, 5)
            && substr((string) $incoming['ends_at'], 0, 5) === substr((string) $existing->ends_at, 0, 5)
            && (int) $incoming['classroom_id'] !== (int) $existing->classroom_id;
    }

    private function time(LessonSchedule $schedule): string
    {
        return $schedule->day_of_week->label().' pukul '.substr((string) $schedule->starts_at, 0, 5).'–'.substr((string) $schedule->ends_at, 0, 5);
    }
}
