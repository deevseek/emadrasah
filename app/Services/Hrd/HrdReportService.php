<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Models\{AcademicYear, Personnel, PersonnelAttendance, PersonnelCashAdvance, PersonnelLeaveRequest, PersonnelPayroll, Semester};
use Carbon\CarbonInterface;

class HrdReportService
{
    public function data(CarbonInterface $start, CarbonInterface $end, bool $paginate = true): array
    {
        $range = [$start->toDateString(), $end->toDateString()];
        $attendance = PersonnelAttendance::query()->with('personnel')->whereBetween('attendance_date', $range);
        $payroll = PersonnelPayroll::query()->whereBetween('period_start', $range);
        $leaves = PersonnelLeaveRequest::query()->whereBetween('start_date', $range);
        $advances = PersonnelCashAdvance::query()->whereBetween('request_date', $range);
        $orderedAttendance = (clone $attendance)->orderBy('attendance_date')->orderBy(
            Personnel::query()->select('full_name')->whereColumn('personnel.id', 'personnel_attendances.personnel_id')
        );

        return [
            'start' => $start,
            'end' => $end,
            'attendances' => $paginate ? $orderedAttendance->paginate(20)->withQueryString() : $orderedAttendance->get(),
            'attendanceSummary' => (clone $attendance)->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status'),
            'minutes' => (clone $attendance)->selectRaw('sum(late_minutes) late, sum(overtime_minutes) overtime')->first(),
            'payrollSummary' => (clone $payroll)->selectRaw('count(*) employees, sum(base_salary) base, sum(allowance) allowance, sum(deduction + late_deduction + cash_advance_deduction) deductions, sum(cash_advance_deduction) cash_advance, sum(total) net')->first(),
            'leaveSummary' => (clone $leaves)->selectRaw('leave_type, count(*) total')->groupBy('leave_type')->pluck('total', 'leave_type'),
            'advanceSummary' => (clone $advances)->selectRaw("sum(case when status in ('disbursed', 'partially_paid', 'paid') then amount else 0 end) disbursed, sum(amount - remaining_amount) paid, sum(remaining_amount) remaining")->first(),
            'academicYear' => AcademicYear::query()->whereDate('starts_at', '<=', $end)->whereDate('ends_at', '>=', $start)->orderByDesc('is_active')->first(),
            'semester' => Semester::query()->whereDate('starts_at', '<=', $end)->whereDate('ends_at', '>=', $start)->orderByDesc('is_active')->first(),
        ];
    }
}
