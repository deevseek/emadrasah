<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentComponent extends Model
{
    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'classroom_id',
        'subject_id',
        'teaching_assignment_id',
        'employee_id',
        'name',
        'type',
        'weight',
        'maximum_score',
        'assessment_date',
        'description',
        'is_required',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'maximum_score' => 'decimal:2',
            'assessment_date' => 'date',
            'is_required' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class);
    }
}
