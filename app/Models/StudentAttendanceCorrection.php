<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendanceCorrection extends Model
{
    protected $fillable = ['student_attendance_id','corrected_by','old_status','new_status','old_values','new_values','reason'];
    protected function casts(): array { return ['old_values'=>'array','new_values'=>'array']; }
    public function attendance(): BelongsTo { return $this->belongsTo(StudentAttendance::class, 'student_attendance_id'); }
    public function corrector(): BelongsTo { return $this->belongsTo(User::class, 'corrected_by'); }
}
