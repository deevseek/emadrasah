<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\Finance\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StudentDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('student-discounts.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fee_type_id' => $this->filled('fee_type_id') ? $this->input('fee_type_id') : null,
            'semester_id' => $this->filled('semester_id') ? $this->input('semester_id') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'fee_type_id' => ['nullable', 'integer', 'exists:fee_types,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when(
                    $this->input('discount_type') === DiscountType::Percentage->value,
                    ['max:100'],
                ),
            ],
            'maximum_discount' => ['nullable', 'numeric', 'min:0'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
