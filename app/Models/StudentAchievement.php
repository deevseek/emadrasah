<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'semester_id',
        'achievement_type',
        'name',
        'level',
        'rank',
        'achievement_date',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'achievement_date' => 'date',
        ];
    }
}
