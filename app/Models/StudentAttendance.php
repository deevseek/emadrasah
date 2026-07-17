<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentAttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StudentAttendance extends Model
{
    protected $fillable = [
        'student_attendance_session_id',
        'student_id',
        'student_enrollment_id',
        'classroom_id',
        'academic_year_id',
        'semester_id',
        'attendance_date',
        'status',
        'arrival_time',
        'departure_time',
        'late_minutes',
        'early_leave_minutes',
        'reason',
        'notes',
        'attachment_path',
        'finalized_by',
        'finalized_at',
        'corrected_by',
        'corrected_at',
        'correction_reason',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'status' => StudentAttendanceStatus::class,
            'finalized_at' => 'datetime',
            'corrected_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(StudentAttendanceSession::class, 'student_attendance_session_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
    public function corrections(): HasMany
    {
        return $this->hasMany(StudentAttendanceCorrection::class);
    }

}
