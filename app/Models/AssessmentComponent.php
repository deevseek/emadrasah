<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentComponent extends Model
{
    protected $fillable = ['academic_year_id', 'semester_id', 'classroom_id', 'subject_id', 'teaching_assignment_id', 'employee_id', 'name', 'type', 'weight', 'maximum_score', 'assessment_date', 'description', 'is_required', 'status', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_required' => 'boolean',
            'needs_guidance' => 'boolean',
            'joined_at' => 'date',
            'completed_at' => 'date',
            'journal_date' => 'date',
            'assessment_date' => 'date',
            'achievement_date' => 'date',
            'report_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'published_at' => 'datetime',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class);
    }
}
