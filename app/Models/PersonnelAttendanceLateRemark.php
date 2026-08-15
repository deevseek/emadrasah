<?php
declare(strict_types=1);
namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PersonnelAttendanceLateRemark extends Model{protected $guarded=[];protected function casts():array{return ['decision_at'=>'datetime'];}public function attendance():BelongsTo{return $this->belongsTo(PersonnelAttendance::class,'attendance_id');}}
