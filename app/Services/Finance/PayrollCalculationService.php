<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\PayrollStatus;
use App\Enums\Finance\SalaryComponentType;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Finance\EmployeePayroll;
use App\Models\Finance\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PayrollCalculationService
{
    public function calculate(PayrollPeriod $period): void
    {
        throw_if(in_array($period->status, [PayrollStatus::Approved->value, PayrollStatus::Paid->value, PayrollStatus::Closed->value], true), ValidationException::withMessages(['period' => 'Payroll terkunci.']));
        DB::transaction(function () use ($period): void {
            Employee::query()->where('is_active', true)->with('salaryComponents.salaryComponent')->each(function (Employee $employee) use ($period): void {
                $payroll = EmployeePayroll::updateOrCreate(['payroll_period_id' => $period->id, 'employee_id' => $employee->id], ['status' => PayrollStatus::Calculated->value, 'calculated_at' => now()]);
                $payroll->items()->delete();
                $earnings = '0'; $deductions = '0';
                $attendance = EmployeeAttendance::query()->where('employee_id', $employee->id)->whereBetween('attendance_date', [$period->starts_on, $period->ends_on])->get();
                foreach ($employee->salaryComponents()->where('is_active', true)->get() as $structure) {
                    $component = $structure->salaryComponent; if (! $component?->is_active) { continue; }
                    $quantity = $component->is_attendance_based ? (string) max(1, $attendance->count()) : '1';
                    $rate = (string) ($structure->amount ?? $component->default_amount ?? 0);
                    $amount = bcmul($quantity, $rate, 2);
                    $payroll->items()->create(['salary_component_id' => $component->id, 'component_name_snapshot' => $component->name, 'component_type' => $component->component_type, 'quantity' => $quantity, 'rate' => $rate, 'amount' => $amount]);
                    if ($component->component_type === SalaryComponentType::Deduction->value) { $deductions = bcadd($deductions, $amount, 2); } else { $earnings = bcadd($earnings, $amount, 2); }
                }
                $payroll->update(['total_earnings' => $earnings, 'total_deductions' => $deductions, 'net_salary' => bcsub($earnings, $deductions, 2), 'attendance_present' => $attendance->count()]);
            });
            $period->update(['status' => PayrollStatus::Calculated->value]);
            activity('payroll')->performedOn($period)->causedBy(auth()->user())->event('payroll.calculated')->log('Payroll dihitung');
        });
    }
}
