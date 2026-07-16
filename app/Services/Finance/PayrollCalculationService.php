<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\AttendanceStatus;
use App\Enums\Finance\PayrollStatus;
use App\Enums\Finance\SalaryCalculationType;
use App\Enums\Finance\SalaryComponentType;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Finance\EmployeePayroll;
use App\Models\Finance\EmployeeSalaryComponent;
use App\Models\Finance\PayrollPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PayrollCalculationService
{
    public function calculate(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $period = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->getKey());

            if (! in_array($period->status, [
                PayrollStatus::Draft->value,
                PayrollStatus::Calculated->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'period' => 'Payroll hanya dapat dihitung dari status draft atau calculated.',
                ]);
            }

            Employee::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->each(function (Employee $employee) use ($period): void {
                    $this->calculateEmployee($employee, $period);
                });

            if (! $period->payrolls()->exists()) {
                throw ValidationException::withMessages([
                    'period' => 'Tidak ada pegawai aktif yang dapat dihitung.',
                ]);
            }

            $period->update(['status' => PayrollStatus::Calculated->value]);

            activity('payroll')
                ->performedOn($period)
                ->causedBy(auth()->user())
                ->event('payroll.calculated')
                ->log('Payroll dihitung');
        });
    }

    private function calculateEmployee(
        Employee $employee,
        PayrollPeriod $period,
    ): void {
        $attendance = EmployeeAttendance::query()
            ->where('employee_id', $employee->getKey())
            ->whereBetween('attendance_date', [
                $period->starts_on,
                $period->ends_on,
            ])
            ->get();

        $structures = EmployeeSalaryComponent::query()
            ->with('salaryComponent')
            ->where('employee_id', $employee->getKey())
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $period->ends_on)
            ->where(function ($query) use ($period): void {
                $query
                    ->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $period->starts_on);
            })
            ->get()
            ->filter(static fn (EmployeeSalaryComponent $structure): bool => (bool) $structure->salaryComponent?->is_active);

        $payroll = EmployeePayroll::query()->updateOrCreate([
            'payroll_period_id' => $period->getKey(),
            'employee_id' => $employee->getKey(),
        ], [
            'status' => PayrollStatus::Calculated->value,
            'calculated_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'paid_at' => null,
            'payment_method' => null,
            'reference_number' => null,
            'financial_transaction_id' => null,
        ]);

        $payroll->items()->delete();

        $attendanceSummary = $this->attendanceSummary($attendance);
        $earnings = '0.00';
        $deductions = '0.00';
        $basicSalary = '0.00';
        $percentageStructures = collect();

        foreach ($structures as $structure) {
            $component = $structure->salaryComponent;

            if ($component->calculation_type === SalaryCalculationType::Percentage->value) {
                $percentageStructures->push($structure);

                continue;
            }

            [$quantity, $rate, $amount] = $this->calculateFixedComponent(
                $structure,
                $attendanceSummary,
            );

            $this->createPayrollItem($payroll, $structure, $quantity, $rate, $amount);
            [$earnings, $deductions] = $this->accumulate(
                $component->component_type,
                $amount,
                $earnings,
                $deductions,
            );

            if (
                $component->code === 'GAJI-POKOK'
                && $component->component_type === SalaryComponentType::Earning->value
            ) {
                $basicSalary = $amount;
            }
        }

        if (bccomp($basicSalary, '0', 2) <= 0) {
            $basicSalary = $earnings;
        }

        foreach ($percentageStructures as $structure) {
            $component = $structure->salaryComponent;
            $percentage = (string) (
                $structure->percentage
                ?? $component->percentage
                ?? 0
            );
            $amount = bcdiv(
                bcmul($basicSalary, $percentage, 4),
                '100',
                2,
            );

            $this->createPayrollItem(
                $payroll,
                $structure,
                '1.00',
                $percentage,
                $amount,
                'Persentase dari gaji pokok',
            );
            [$earnings, $deductions] = $this->accumulate(
                $component->component_type,
                $amount,
                $earnings,
                $deductions,
            );
        }

        $netSalary = bcsub($earnings, $deductions, 2);

        if (bccomp($netSalary, '0', 2) < 0) {
            throw ValidationException::withMessages([
                'payroll' => "Total potongan pegawai {$employee->name} melebihi pendapatan.",
            ]);
        }

        $payroll->update([
            'basic_salary' => $basicSalary,
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'net_salary' => $netSalary,
            'attendance_present' => $attendanceSummary['present'],
            'attendance_late' => $attendanceSummary['late'],
            'attendance_permission' => $attendanceSummary['permission'],
            'attendance_sick' => $attendanceSummary['sick'],
            'attendance_alpha' => $attendanceSummary['alpha'],
        ]);
    }

    private function calculateFixedComponent(
        EmployeeSalaryComponent $structure,
        array $attendanceSummary,
    ): array {
        $component = $structure->salaryComponent;
        $rate = (string) (
            $structure->amount
            ?? $component->default_amount
            ?? 0
        );
        $quantity = '1.00';

        if (
            $component->is_attendance_based
            || $component->calculation_type === SalaryCalculationType::Attendance->value
        ) {
            $quantity = (string) (
                $attendanceSummary['present']
                + $attendanceSummary['late']
                + $attendanceSummary['duty']
            );
        }

        return [
            $quantity,
            $rate,
            bcmul($quantity, $rate, 2),
        ];
    }

    private function createPayrollItem(
        EmployeePayroll $payroll,
        EmployeeSalaryComponent $structure,
        string $quantity,
        string $rate,
        string $amount,
        ?string $notes = null,
    ): void {
        $component = $structure->salaryComponent;

        $payroll->items()->create([
            'salary_component_id' => $component->getKey(),
            'component_name_snapshot' => $component->name,
            'component_type' => $component->component_type,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount,
            'notes' => $notes,
        ]);
    }

    private function accumulate(
        string $componentType,
        string $amount,
        string $earnings,
        string $deductions,
    ): array {
        if ($componentType === SalaryComponentType::Deduction->value) {
            $deductions = bcadd($deductions, $amount, 2);
        } else {
            $earnings = bcadd($earnings, $amount, 2);
        }

        return [$earnings, $deductions];
    }

    private function attendanceSummary(Collection $attendance): array
    {
        $count = static fn (AttendanceStatus $status): int => $attendance
            ->filter(static fn (EmployeeAttendance $record): bool => $record->status === $status)
            ->count();

        return [
            'present' => $count(AttendanceStatus::Present),
            'late' => $count(AttendanceStatus::Late),
            'permission' => $count(AttendanceStatus::Leave),
            'sick' => $count(AttendanceStatus::Sick),
            'duty' => $count(AttendanceStatus::Duty),
            'alpha' => $count(AttendanceStatus::Alpha),
        ];
    }
}
