<?php

declare(strict_types=1);

namespace App\Actions\Hrd;

use App\Models\{Personnel, PersonnelPayroll, User};
use App\Services\Hrd\{CashAdvanceService, PayrollCalculator};
use App\Services\Settings\ApplicationSettingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProcessAttendancePayroll
{
    public function __construct(
        private PayrollCalculator $calculator,
        private CashAdvanceService $cashAdvances,
        private ApplicationSettingService $settings,
    ) {}

    public function handle(Personnel $personnel, array $data, User $actor): PersonnelPayroll
    {
        $calculation = $this->calculator->calculate(
            $personnel,
            CarbonImmutable::parse($data['period_start']),
            CarbonImmutable::parse($data['period_end']),
            (float) ($data['allowance'] ?? 0),
            (float) ($data['deduction'] ?? 0),
        );

        return DB::transaction(function () use ($personnel, $data, $actor, $calculation): PersonnelPayroll {
            $advances = $this->settings->get('hrd_payroll_auto_cash_advance_deduction_enabled', false)
                ? $personnel->cashAdvances()->whereIn('status', ['disbursed', 'partially_paid'])->lockForUpdate()->get()
                : collect();
            $cashDeduction = $advances->sum(fn ($advance): float => min((float) $advance->remaining_amount, (float) $advance->installment_amount));

            $payroll = PersonnelPayroll::create([
                'personnel_id' => $personnel->id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'pay_date' => $data['pay_date'] ?? null,
                'monthly_salary' => $calculation['monthly'],
                'base_salary' => $calculation['base'],
                'attendance_days' => $calculation['days'],
                'daily_salary' => $calculation['daily'],
                'calculation_method' => $calculation['byAttendance'] ? 'attendance' : 'monthly',
                'attendance_summary' => $calculation['summary'],
                'allowance' => $calculation['allowance'],
                'deduction' => $calculation['deduction'],
                'late_deduction' => $calculation['late'],
                'cash_advance_deduction' => $cashDeduction,
                'total' => max(0, $calculation['total'] - $cashDeduction),
                'status' => 'processed',
                'note' => $data['note'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($advances as $advance) {
                $amount = min((float) $advance->remaining_amount, (float) $advance->installment_amount);
                if ($amount > 0) {
                    $this->cashAdvances->pay($advance, $amount, 'payroll_deduction', $actor, $payroll->id);
                }
            }

            activity('hrd')->causedBy($actor)->performedOn($payroll)->withProperties([
                'calculation_method' => $payroll->calculation_method,
                'attendance_summary' => $payroll->attendance_summary,
            ])->log('HRD memproses payroll.');

            return $payroll;
        });
    }
}
