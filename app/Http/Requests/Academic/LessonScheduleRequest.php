<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Enums\DayOfWeek;
use App\Models\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['academic_year_id' => ['required', 'exists:academic_years,id'], 'semester_id' => ['required', 'exists:semesters,id', fn ($a, $v, $f) => $v && Semester::find($v)?->academic_year_id != $this->input('academic_year_id') ? $f('Semester harus sesuai tahun ajaran.') : null], 'classroom_id' => ['required', 'exists:classrooms,id'], 'subject_id' => ['required', 'exists:subjects,id'], 'employee_id' => ['required', 'exists:employees,id'], 'day_of_week' => ['required', Rule::enum(DayOfWeek::class)], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'room' => ['nullable', 'string', 'max:255'], 'is_active' => ['nullable', 'boolean']];
    }
}
