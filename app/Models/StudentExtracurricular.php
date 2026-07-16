<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentExtracurricular extends Model
{
    protected $fillable = [
        'student_id',
        'extracurricular_id',
        'academic_year_id',
        'semester_id',
        'predicate',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
