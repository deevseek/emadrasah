<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\WorkScheduleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmployeeAttendance extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'checked_in_at',
        'checked_out_at',
        'status',
        'work_schedule_type',
        'latitude',
        'longitude',
        'accuracy',
        'location_text',
        'selfie_path',
        'notes',
        'is_verified',
        'verified_by',
        'verified_at',
        'correction_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'verified_at' => 'datetime',
            'status' => AttendanceStatus::class,
            'work_schedule_type' => WorkScheduleType::class,
            'is_verified' => 'boolean',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('attendance_date', $date))
            ->when($filters['month'] ?? null, fn (Builder $query, string $month): Builder => $query->whereMonth('attendance_date', substr($month, 5, 2))->whereYear('attendance_date', substr($month, 0, 4)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['employee_id'] ?? null, fn (Builder $query, string $employeeId): Builder => $query->where('employee_id', $employeeId))
            ->when($filters['work_schedule_type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('work_schedule_type', $type));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
