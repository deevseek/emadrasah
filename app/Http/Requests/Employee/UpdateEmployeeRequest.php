<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employees.update') ?? false; }

    protected function prepareForValidation(): void
    {
        $this->merge(['employment_type' => $this->employmentTypeFromPosition((string) $this->input('position'))]);
    }

    public function rules(): array
    {
        $id = $this->route('employee')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'employee_status' => ['required', Rule::enum(EmployeeStatus::class)],
            'employee_number' => ['nullable', 'string', 'max:100', Rule::unique('employees', 'employee_number')->ignore($id)],
            'nip' => ['nullable', 'string', 'max:100', Rule::unique('employees', 'nip')->ignore($id)],
            'rank_grade' => ['nullable', 'string', 'max:150'],
            'peg_id' => ['nullable', 'string', 'max:100'],
            'last_education' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:150'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'certification_status' => ['nullable', 'string', 'max:150'],
            'certification_subject' => ['nullable', 'string', 'max:150'],
            'weekly_teaching_hours' => ['nullable', 'integer', 'min:0', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($id)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array { return (new StoreEmployeeRequest)->attributes(); }

    protected function employmentTypeFromPosition(string $position): string
    {
        $position = Str::lower($position);

        return match (true) {
            str_contains($position, 'kepala') => EmploymentType::Principal->value,
            str_contains($position, 'kelas') => EmploymentType::ClassTeacher->value,
            str_contains($position, 'btaq') => EmploymentType::BtaqTeacher->value,
            str_contains($position, 'usaha') => EmploymentType::Administration->value,
            str_contains($position, 'bersih') => EmploymentType::EducationStaff->value,
            default => EmploymentType::SubjectTeacher->value,
        };
    }
}
