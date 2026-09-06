<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\HasOne;
class PersonnelAttendanceAudit extends Model{protected $guarded=[];protected function casts():array{return['risk_flags'=>'array','face_verified'=>'boolean','occurred_at'=>'datetime'];}public function faceVerification():HasOne{return $this->hasOne(AttendanceFaceVerification::class,'challenge_id','challenge_id');}}
