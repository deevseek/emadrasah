<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\EmployeeAttendance;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;

final class AttendanceService
{
    public function statusAndMinutes(CarbonImmutable $time, WorkSchedule $schedule): array
    {
        $scheduled = $time->setTimeFromTimeString($schedule->check_in_time)->addMinutes((int) $schedule->late_tolerance_minutes);
        $late = max(0, $scheduled->diffInMinutes($time, false));
        return [$late > 0 ? \App\Enums\AttendanceStatus::Late : \App\Enums\AttendanceStatus::Present, $late];
    }

    public function earlyLeaveMinutes(CarbonImmutable $time, EmployeeAttendance $attendance): int
    {
        if (! $attendance->scheduled_check_out) return 0;
        $scheduled = $time->setTimeFromTimeString((string) $attendance->scheduled_check_out);
        return max(0, $time->diffInMinutes($scheduled, false));
    }
}
