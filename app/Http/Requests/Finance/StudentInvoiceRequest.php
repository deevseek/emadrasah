<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StudentInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('post')
            ? 'student-invoices.create'
            : 'student-invoices.update';

        return $this->user()?->can($permission) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'semester_id' => $this->filled('semester_id') ? $this->input('semester_id') : null,
            'billing_period_id' => $this->filled('billing_period_id') ? $this->input('billing_period_id') : null,
            'discount_amount' => $this->filled('discount_amount') ? $this->input('discount_amount') : 0,
            'penalty_amount' => $this->filled('penalty_amount') ? $this->input('penalty_amount') : 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'billing_period_id' => ['nullable', 'integer', 'exists:billing_periods,id'],
            'fee_type_id' => [
                'required',
                'integer',
                Rule::exists('fee_types', 'id')->where('is_active', true),
            ],
            'original_amount' => ['required', 'numeric', 'gt:0'],
            'discount_amount' => ['required', 'numeric', 'min:0', 'lte:original_amount'],
            'penalty_amount' => ['required', 'numeric', 'min:0'],
            'due_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
