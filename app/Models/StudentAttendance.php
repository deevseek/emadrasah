<?php
declare(strict_types=1);
namespace App\Models;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
class StudentAttendance extends Model { protected $guarded=[]; protected function casts():array{return ['attendance_date'=>'date','status'=>AttendanceStatus::class];} public function student(){return $this->belongsTo(Student::class);} public function classroom(){return $this->belongsTo(Classroom::class);} }
