<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\Finance\SalaryCalculationType;
use App\Enums\Finance\SalaryComponentType;
use App\Models\Finance\SalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('salary-components.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'taxable' => $this->boolean('taxable'),
            'is_attendance_based' => $this->boolean('is_attendance_based'),
            'is_active' => $this->boolean('is_active'),
            'expense_account_id' => $this->filled('expense_account_id') ? $this->input('expense_account_id') : null,
            'payable_account_id' => $this->filled('payable_account_id') ? $this->input('payable_account_id') : null,
        ]);
    }

    public function rules(): array
    {
        $salaryComponent = $this->route('salaryComponent');
        $salaryComponentId = $salaryComponent instanceof SalaryComponent
            ? $salaryComponent->getKey()
            : $salaryComponent;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('salary_components', 'code')->ignore($salaryComponentId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'component_type' => ['required', Rule::enum(SalaryComponentType::class)],
            'calculation_type' => ['required', Rule::enum(SalaryCalculationType::class)],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'between:0,100'],
            'taxable' => ['required', 'boolean'],
            'is_attendance_based' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'expense_account_id' => ['nullable', 'integer', 'exists:chart_accounts,id'],
            'payable_account_id' => ['nullable', 'integer', 'exists:chart_accounts,id'],
        ];
    }
}
