<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Models\Finance\BillingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BillingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('billing-periods.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'semester_id' => $this->filled('semester_id') ? $this->input('semester_id') : null,
            'month' => $this->filled('month') ? $this->input('month') : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $billingPeriod = $this->route('billingPeriod');
        $billingPeriodId = $billingPeriod instanceof BillingPeriod
            ? $billingPeriod->getKey()
            : $billingPeriod;

        return [
            'academic_year_id' => [
                'required',
                'integer',
                'exists:academic_years,id',
                Rule::unique('billing_periods', 'academic_year_id')
                    ->where(fn ($query) => $query
                        ->where('semester_id', $this->input('semester_id'))
                        ->where('month', $this->input('month'))
                        ->where('year', $this->input('year')))
                    ->ignore($billingPeriodId),
            ],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
