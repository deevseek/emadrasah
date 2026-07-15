<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('classroom')?->id;
        $year = $this->input('academic_year_id');

        return ['academic_year_id' => ['required', 'exists:academic_years,id'], 'grade_level_id' => ['required', 'exists:grade_levels,id'], 'name' => ['required', 'string', 'max:255', Rule::unique('classrooms', 'name')->where('academic_year_id', $year)->ignore($id)], 'code' => ['required', 'string', 'max:50', Rule::unique('classrooms', 'code')->where('academic_year_id', $year)->ignore($id)], 'capacity' => ['nullable', 'integer', 'min:1', 'max:999'], 'homeroom_teacher_id' => ['nullable', 'exists:employees,id', fn ($a, $v, $f) => $v && ! Employee::whereKey($v)->where('is_active', true)->exists() ? $f('Wali kelas harus pegawai aktif.') : null], 'room' => ['nullable', 'string', 'max:255'], 'is_active' => ['nullable', 'boolean']];
    }
}
