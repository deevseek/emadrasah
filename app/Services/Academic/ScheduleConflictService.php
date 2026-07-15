<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\LessonSchedule;
use App\Models\TeachingAssignment;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    public function validate(array $data, ?LessonSchedule $ignore = null): void
    {
        if (($data['ends_at'] ?? null) <= ($data['starts_at'] ?? null)) {
            throw ValidationException::withMessages(['ends_at' => 'Waktu selesai harus setelah waktu mulai.']);
        }
        $assignment = TeachingAssignment::where('academic_year_id', $data['academic_year_id'])->where('semester_id', $data['semester_id'])->where('employee_id', $data['employee_id'])->where('classroom_id', $data['classroom_id'])->where('subject_id', $data['subject_id'])->where('is_active', true)->exists();
        if (! $assignment) { throw ValidationException::withMessages(['subject_id' => 'Jadwal harus sesuai penugasan mengajar aktif.']); }
        $overlap = fn ($q) => $q->where('day_of_week', $data['day_of_week'])->where('is_active', true)->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at'])->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id));
        if (LessonSchedule::where('employee_id', $data['employee_id'])->where($overlap)->exists()) { throw ValidationException::withMessages(['employee_id' => 'Guru memiliki jadwal lain pada waktu yang sama.']); }
        if (LessonSchedule::where('classroom_id', $data['classroom_id'])->where($overlap)->exists()) { throw ValidationException::withMessages(['classroom_id' => 'Kelas memiliki pelajaran lain pada waktu yang sama.']); }
    }
}
