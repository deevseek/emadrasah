<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSchedule extends Model
{
    protected $fillable = ['teaching_assignment_id', 'academic_year_id', 'semester_id', 'classroom_id', 'subject_id', 'employee_id', 'day_of_week', 'starts_at', 'ends_at', 'lesson_hours', 'room', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['day_of_week' => DayOfWeek::class, 'lesson_hours' => 'integer', 'is_active' => 'boolean'];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
