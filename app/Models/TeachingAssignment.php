<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'employee_id', 'classroom_id', 'subject_id', 'weekly_hours', 'is_active', 'starts_on', 'ends_on', 'notes', 'replaced_by_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'weekly_hours' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function schedules(): HasMany { return $this->hasMany(LessonSchedule::class); }
}
