<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentScore extends Model
{
    protected $fillable = [
        'assessment_component_id',
        'student_id',
        'score',
        'remedial_score',
        'final_score',
        'predicate',
        'notes',
        'entered_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'remedial_score' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }
}
