<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCard extends Model
{
    protected $fillable = [
        'student_id',
        'student_enrollment_id',
        'classroom_id',
        'academic_year_id',
        'semester_id',
        'homeroom_teacher_id',
        'document_number',
        'status',
        'generated_at',
        'submitted_at',
        'approved_by',
        'approved_at',
        'locked_at',
        'reopened_by',
        'reopened_at',
        'homeroom_notes',
        'general_notes',
        'place',
        'report_date',
        'sick_count',
        'permission_count',
        'alpha_count',
        'late_count',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'reopened_at' => 'datetime',
            'report_date' => 'date',
            'sick_count' => 'integer',
            'permission_count' => 'integer',
            'alpha_count' => 'integer',
            'late_count' => 'integer',
        ];
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(ReportCardSubject::class);
    }
}
