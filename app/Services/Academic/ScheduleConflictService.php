<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\LessonSchedule;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    public function assertNoConflict(array $data, ?LessonSchedule $ignore = null): void
    {
        $base = LessonSchedule::with(['employee','classroom'])->where('is_active', true)->where('semester_id', $data['semester_id'])->where('day_of_week', $data['day_of_week'])->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at']);
        if ($ignore) $base->whereKeyNot($ignore->id);
        $teacher = (clone $base)->where('employee_id', $data['employee_id'])->first();
        if ($teacher) throw ValidationException::withMessages(['teaching_assignment_id' => 'Jadwal tidak dapat disimpan karena '.$teacher->employee?->name.' sudah mengajar '.$teacher->classroom?->name.' pada '.$this->time($teacher).'.']);
        $class = (clone $base)->where('classroom_id', $data['classroom_id'])->first();
        if ($class) throw ValidationException::withMessages(['teaching_assignment_id' => 'Jadwal tidak dapat disimpan karena '.$class->classroom?->name.' sudah memiliki pelajaran pada '.$this->time($class).'.']);
        if (filled($data['room'] ?? null)) {
            $room = (clone $base)->where('room', $data['room'])->first();
            if ($room) throw ValidationException::withMessages(['room' => 'Ruangan '.$data['room'].' sudah digunakan pada '.$this->time($room).'.']);
        }
        $dup = LessonSchedule::where('is_active', true)->where('teaching_assignment_id', $data['teaching_assignment_id'])->where('day_of_week',$data['day_of_week'])->where('starts_at',$data['starts_at'])->where('ends_at',$data['ends_at']);
        if ($ignore) $dup->whereKeyNot($ignore->id);
        if ($dup->exists()) throw ValidationException::withMessages(['starts_at' => 'Penugasan ini sudah memiliki jadwal identik.']);
    }
    private function time(LessonSchedule $s): string { return $s->day_of_week->label().' pukul '.substr((string)$s->starts_at,0,5).'–'.substr((string)$s->ends_at,0,5); }
}
