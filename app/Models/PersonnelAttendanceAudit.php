<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PersonnelAttendanceAudit extends Model{protected $guarded=[];protected function casts():array{return['risk_flags'=>'array','face_verified'=>'boolean','occurred_at'=>'datetime'];}}
