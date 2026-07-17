<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceVerificationStatus;
use App\Enums\WorkScheduleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EmployeeAttendance extends Model
{
    protected $fillable = ['employee_id','attendance_date','work_schedule_id','scheduled_check_in','scheduled_check_out','checked_in_at','checked_out_at','status','work_schedule_type','late_minutes','early_leave_minutes','latitude','longitude','accuracy','location_text','selfie_path','check_in_latitude','check_in_longitude','check_out_latitude','check_out_longitude','check_in_accuracy','check_out_accuracy','check_in_photo_path','check_out_photo_path','notes','is_verified','verification_status','verification_notes','verified_by','verified_at','correction_reason','source','created_by','updated_by'];

    protected function casts(): array
    {
        return ['attendance_date'=>'date','checked_in_at'=>'datetime','checked_out_at'=>'datetime','verified_at'=>'datetime','status'=>AttendanceStatus::class,'work_schedule_type'=>WorkScheduleType::class,'verification_status'=>AttendanceVerificationStatus::class,'is_verified'=>'boolean'];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query->when($filters['date'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('attendance_date', $date))
            ->when($filters['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('attendance_date', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('attendance_date', '<=', $date))
            ->when($filters['month'] ?? null, fn (Builder $q, string $month): Builder => $q->whereMonth('attendance_date', substr($month, 5, 2))->whereYear('attendance_date', substr($month, 0, 4)))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))
            ->when($filters['verification_status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('verification_status', $status))
            ->when($filters['employee_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('employee_id', $id))
            ->when($filters['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('employee', fn (Builder $e) => $e->where('name', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%")->orWhere('employee_number', 'like', "%{$term}%")));
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function workSchedule(): BelongsTo { return $this->belongsTo(WorkSchedule::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function corrections(): HasMany { return $this->hasMany(AttendanceCorrection::class); }
}
