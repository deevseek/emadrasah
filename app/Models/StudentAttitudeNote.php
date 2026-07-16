<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttitudeNote extends Model
{
    protected $fillable = [
        'student_id',
        'classroom_id',
        'academic_year_id',
        'semester_id',
        'spiritual_notes',
        'social_notes',
        'discipline_notes',
        'responsibility_notes',
        'general_notes',
        'entered_by',
    ];
}
