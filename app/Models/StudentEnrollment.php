<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    protected $fillable = ['student_id', 'academic_year_id', 'classroom_id', 'enrollment_number', 'enrolled_at', 'completed_at', 'enrollment_status', 'notes'];

    protected function casts(): array
    {
        return ['enrolled_at' => 'date', 'completed_at' => 'date', 'enrollment_status' => EnrollmentStatus::class];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
