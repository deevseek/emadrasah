<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;

final class AttendanceScheduleService
{
    public function timezone(): string { return config('app.timezone', 'Asia/Jakarta'); }

    public function forEmployeeOn(Employee $employee, CarbonImmutable $date): ?WorkSchedule
    {
        if ($this->isHoliday($date)) return null;
        $assigned = $employee->workScheduleAssignments()->with('workSchedule')->where('is_active', true)->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))->latest('effective_from')->first()?->workSchedule;
        $schedule = $assigned ?: WorkSchedule::query()->where('is_active', true)->where(function ($q) use ($employee): void { $q->whereNull('employee_type')->orWhere('employee_type', $employee->employment_type?->value); })->first();
        if (! $schedule) return null;
        $map = [1=>'senin',2=>'selasa',3=>'rabu',4=>'kamis',5=>'jumat',6=>'sabtu',7=>'minggu'];
        return in_array($map[$date->dayOfWeekIso], $schedule->working_days ?? [], true) ? $schedule : null;
    }

    public function isHoliday(CarbonImmutable $date): bool
    {
        return \DB::table('school_holidays')->whereDate('holiday_date', $date->toDateString())->exists();
    }
}
