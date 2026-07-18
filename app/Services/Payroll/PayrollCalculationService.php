<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Enums\Payroll\PayrollComponentType;
use App\Enums\Payroll\PayrollRunStatus;
use App\Models\Employee;
use App\Models\Payroll\EmployeeSalaryProfile;
use App\Models\Payroll\PayrollItem;
use App\Models\Payroll\PayrollItemComponent;
use App\Models\Payroll\PayrollPeriod;
use App\Models\Payroll\PayrollRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    public function generate(PayrollPeriod $period, array $employeeIds, int $userId): PayrollRun
    {
        return DB::transaction(function () use ($period, $employeeIds, $userId): PayrollRun {
            $period = PayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            if (in_array($period->status, ['final','paid','cancelled'], true)) throw ValidationException::withMessages(['period' => 'Periode payroll sudah terkunci.']);
            $run = PayrollRun::query()->firstOrCreate(['payroll_period_id' => $period->id, 'status' => PayrollRunStatus::Calculated->value], ['run_number' => 'PR-'.now()->format('Ym').'-'.str_pad((string) $period->id, 4, '0', STR_PAD_LEFT), 'generated_at' => now(), 'generated_by' => $userId]);
            $employees = Employee::query()->where('is_active', true)->when($employeeIds !== [], fn($q)=>$q->whereIn('id', $employeeIds))->get();
            foreach ($employees as $employee) { $profile = EmployeeSalaryProfile::query()->where('employee_id',$employee->id)->where('is_active',true)->whereDate('effective_start_date','<=',$period->period_end_date)->where(fn($q)=>$q->whereNull('effective_end_date')->orWhereDate('effective_end_date','>=',$period->period_start_date))->with('components')->first(); if (! $profile) continue; $this->calculateEmployee($run, $employee, $profile); }
            $this->refreshTotals($run); $period->update(['status'=>'calculated']); activity('payroll')->performedOn($run)->causedBy(auth()->user())->event('payroll.generated')->log('Payroll Pegawai digenerate'); return $run->refresh();
        });
    }
    public function calculateEmployee(PayrollRun $run, Employee $employee, EmployeeSalaryProfile $profile): PayrollItem
    {
        $gross = (int) $profile->base_salary; $deductions = 0;
        $item = PayrollItem::query()->updateOrCreate(['payroll_run_id'=>$run->id,'employee_id'=>$employee->id], ['employee_salary_profile_id'=>$profile->id,'employee_name_snapshot'=>$employee->fullName(),'employee_number_snapshot'=>$employee->mainNumber(),'employment_type_snapshot'=>(string) ($employee->employment_type?->value ?? $employee->employment_type),'position_snapshot'=>$employee->position,'base_salary'=>(int)$profile->base_salary,'attendance_snapshot'=>['cutoff'=>$run->period?->attendance_cutoff_start?->toDateString().' s.d. '.$run->period?->attendance_cutoff_end?->toDateString(),'hadir'=>0,'terlambat'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0],'status'=>'calculated','payment_status'=>'unpaid']);
        $item->components()->delete();
        PayrollItemComponent::create(['payroll_item_id'=>$item->id,'component_name_snapshot'=>'Gaji Pokok','component_code_snapshot'=>'GAJI-POKOK','component_type'=>'earning','calculation_type_snapshot'=>'fixed','amount'=>(int)$profile->base_salary]);
        foreach ($profile->components()->where('is_active', true)->with('payrollComponent')->get() as $component) { $amount=(int)$component->amount; $type=$component->payrollComponent->component_type; $type === PayrollComponentType::Deduction->value ? $deductions += $amount : $gross += $amount; PayrollItemComponent::create(['payroll_item_id'=>$item->id,'payroll_component_id'=>$component->payroll_component_id,'component_name_snapshot'=>$component->payrollComponent->name,'component_code_snapshot'=>$component->payrollComponent->code,'component_type'=>$type,'calculation_type_snapshot'=>$component->payrollComponent->calculation_type,'calculation_basis_snapshot'=>$component->calculation_basis,'amount'=>$amount]); }
        $item->update(['gross_salary'=>$gross,'total_deductions'=>$deductions,'net_salary'=>max(0,$gross-$deductions)]); return $item;
    }
    public function refreshTotals(PayrollRun $run): void { $run->load('items'); $run->update(['employee_count'=>$run->items->count(),'total_gross'=>$run->items->sum('gross_salary'),'total_deductions'=>$run->items->sum('total_deductions'),'total_net'=>$run->items->sum('net_salary')]); }
}
