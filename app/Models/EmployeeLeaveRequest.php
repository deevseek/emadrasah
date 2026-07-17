<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmployeeLeaveRequest extends Model
{
    protected $fillable = ['employee_id','starts_at','ends_at','total_days','type','reason','attachment_path','status','submitted_at','approved_by','approved_at','rejected_by','rejected_at','rejection_reason','cancelled_at','notes'];

    protected function casts(): array
    {
        return ['starts_at'=>'date','ends_at'=>'date','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime','cancelled_at'=>'datetime','type'=>LeaveType::class,'status'=>LeaveStatus::class];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
}
