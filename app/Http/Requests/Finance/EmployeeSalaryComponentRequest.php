<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Models\Finance\EmployeeSalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EmployeeSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee-salaries.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->filled('amount') ? $this->input('amount') : null,
            'percentage' => $this->filled('percentage') ? $this->input('percentage') : null,
            'effective_until' => $this->filled('effective_until') ? $this->input('effective_until') : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $employeeSalary = $this->route('employeeSalary');
        $employeeSalaryId = $employeeSalary instanceof EmployeeSalaryComponent
            ? $employeeSalary->getKey()
            : $employeeSalary;

        return [
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
                Rule::unique('employee_salary_components', 'employee_id')
                    ->where(fn ($query) => $query
                        ->where('salary_component_id', $this->input('salary_component_id'))
                        ->where('effective_from', $this->input('effective_from')))
                    ->ignore($employeeSalaryId),
            ],
            'salary_component_id' => ['required', 'integer', 'exists:salary_components,id'],
            'amount' => ['nullable', 'numeric', 'min:0', 'required_without:percentage'],
            'percentage' => ['nullable', 'numeric', 'between:0,100', 'required_without:amount'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
