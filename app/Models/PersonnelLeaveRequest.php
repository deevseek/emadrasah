<?php
declare(strict_types=1);
namespace App\Models;use App\Enums\Hrd\LeaveType;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PersonnelLeaveRequest extends Model{protected $guarded=[];protected function casts():array{return ['start_date'=>'date','end_date'=>'date','approved_at'=>'datetime','leave_type'=>LeaveType::class];}public function personnel():BelongsTo{return $this->belongsTo(Personnel::class);}public function approver():BelongsTo{return $this->belongsTo(User::class,'approved_by');}}
