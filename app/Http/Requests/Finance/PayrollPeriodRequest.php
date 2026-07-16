<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Models\Finance\PayrollPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('post')
            ? 'payroll-periods.create'
            : 'payroll-periods.manage';

        return $this->user()?->can($permission) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_date' => $this->filled('payment_date') ? $this->input('payment_date') : null,
        ]);
    }

    public function rules(): array
    {
        $payrollPeriod = $this->route('payrollPeriod');
        $payrollPeriodId = $payrollPeriod instanceof PayrollPeriod
            ? $payrollPeriod->getKey()
            : $payrollPeriod;

        return [
            'name' => ['required', 'string', 'max:255'],
            'month' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('payroll_periods', 'month')
                    ->where(fn ($query) => $query->where('year', $this->input('year')))
                    ->ignore($payrollPeriodId),
            ],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'payment_date' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
