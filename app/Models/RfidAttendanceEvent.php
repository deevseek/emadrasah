<?php
declare(strict_types=1);namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RfidAttendanceEvent extends Model{protected $guarded=[];protected function casts():array{return ['success'=>'boolean','scanned_at'=>'datetime'];}public function student():BelongsTo{return $this->belongsTo(Student::class);}public function attendance():BelongsTo{return $this->belongsTo(StudentAttendance::class,'student_attendance_id');}}
