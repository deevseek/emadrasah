<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeachingJournalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class TeachingJournal extends Model
{
    protected $fillable = [
        'teaching_assignment_id',
        'lesson_schedule_id',
        'employee_id',
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'semester_id',
        'journal_date',
        'starts_at',
        'ends_at',
        'lesson_hours',
        'material',
        'learning_objectives',
        'method',
        'media',
        'assignment',
        'assessment',
        'teacher_notes',
        'status',
        'submitted_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'status' => TeachingJournalStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'teaching_journal_student')
            ->withPivot(['status', 'notes'])
            ->withTimestamps();
    }
}
