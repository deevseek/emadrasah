<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentAttendanceSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAttendanceSession extends Model
{
    protected $fillable = ['classroom_id','academic_year_id','semester_id','attendance_date','status','recorded_by','finalized_by','finalized_at','notes'];
    protected function casts(): array { return ['attendance_date'=>'date','finalized_at'=>'datetime','status'=>StudentAttendanceSessionStatus::class]; }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class,'recorded_by'); }
    public function finalizer(): BelongsTo { return $this->belongsTo(User::class,'finalized_by'); }
    public function attendances(): HasMany { return $this->hasMany(StudentAttendance::class); }
    public function corrections(): HasMany { return $this->hasManyThrough(StudentAttendanceCorrection::class, StudentAttendance::class); }
}
