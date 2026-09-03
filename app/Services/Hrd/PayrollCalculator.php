<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Enums\Hrd\AttendanceStatus;
use App\Models\Personnel;
use App\Services\Settings\ApplicationSettingService;
use Carbon\CarbonImmutable;

class PayrollCalculator
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function divisor(CarbonImmutable $start): int
    {
        return $start->daysInMonth;
    }

    public function calculate(Personnel $personnel, CarbonImmutable $start, CarbonImmutable $end, float $allowance = 0, float $deduction = 0): array
    {
        $monthly = (float) ($personnel->base_salary ?? 0);
        $daily = round($monthly / $this->divisor($start), 2);
        $attendances = $personnel->attendances()
            ->with('lateRemark')
            ->whereBetween('attendance_date', [$start, $end])
            ->get();
        $paidStatuses = [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Sick, AttendanceStatus::Leave, AttendanceStatus::OfficialDuty];
        $days = $attendances->whereIn('status', $paidStatuses)->count();
        $byAttendance = (bool) $this->settings->get('hrd_payroll_by_attendance_enabled', true);
        $base = $byAttendance ? round($daily * $days, 2) : $monthly;
        $summary = collect(AttendanceStatus::cases())->mapWithKeys(
            fn (AttendanceStatus $status): array => [$status->value => $attendances->where('status', $status)->count()],
        )->all();

        $late = 0.0;
        if ($this->settings->get('hrd_payroll_auto_late_deduction_enabled', false)) {
            foreach ($attendances as $attendance) {
                if ($attendance->late_minutes > 0 && $attendance->lateRemark?->status !== 'approved') {
                    $shiftMinutes = $this->shiftMinutes($attendance->shift_number, CarbonImmutable::parse($attendance->attendance_date));
                    $late += round($daily * ((int) $attendance->late_minutes / $shiftMinutes), 2);
                }
            }
        }

        $total = max(0, round($base + $allowance - $deduction - $late, 2));

        return compact('monthly', 'daily', 'days', 'base', 'allowance', 'deduction', 'late', 'total', 'summary', 'byAttendance');
    }

    private function shiftMinutes(int $shift, CarbonImmutable $date): int
    {
        $start = CarbonImmutable::parse($date->toDateString().' '.$this->settings->get("hrd_shift_{$shift}_start", '07:00'));
        $end = CarbonImmutable::parse($date->toDateString().' '.$this->settings->get("hrd_shift_{$shift}_end", '15:00'));
        if ($end <= $start) {
            $end = $end->addDay();
        }

        return max(1, (int) $start->diffInMinutes($end));
    }
}
