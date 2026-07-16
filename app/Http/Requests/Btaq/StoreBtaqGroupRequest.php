<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBtaqGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('btaq-groups.manage') ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    public function attributes(): array
    {
        return ['academic_year_id' => 'tahun ajaran', 'semester_id' => 'semester', 'employee_id' => 'guru BTAQ', 'btaq_level_id' => 'level BTAQ'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    protected function baseRules(?int $ignoreId = null): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('btaq_groups', 'code')->where('academic_year_id', $this->input('academic_year_id'))->ignore($ignoreId)],
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('is_active', true)],
            'btaq_level_id' => ['required', 'exists:btaq_levels,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
