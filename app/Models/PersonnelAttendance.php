<?php
declare(strict_types=1);
namespace App\Models;
use App\Enums\Hrd\AttendanceStatus;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasOne};
class PersonnelAttendance extends Model{protected $guarded=[];protected function casts():array{return ['attendance_date'=>'date','check_in_time'=>'datetime','check_out_time'=>'datetime','status'=>AttendanceStatus::class];}public function personnel():BelongsTo{return $this->belongsTo(Personnel::class);}public function lateRemark():HasOne{return $this->hasOne(PersonnelAttendanceLateRemark::class,'attendance_id');}}
