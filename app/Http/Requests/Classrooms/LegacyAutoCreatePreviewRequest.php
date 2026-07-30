<?php

declare(strict_types=1);

namespace App\Http\Requests\Classrooms;

use Illuminate\Foundation\Http\FormRequest;

class LegacyAutoCreatePreviewRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('classrooms.map-legacy'); }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.original_label' => ['required', 'string', 'max:255'],
            'rows.*.grade_level_id' => ['required', 'exists:grade_levels,id'],
            'rows.*.code' => ['required', 'string', 'max:50'],
            'rows.*.name' => ['nullable', 'string', 'max:150'],
            'rows.*.student_count' => ['required', 'integer', 'min:0'],
            'merge_duplicates' => ['nullable', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['rows.*.grade_level_id.required' => 'Tingkat setiap rombel wajib dipilih.', 'rows.*.code.required' => 'Kode setiap rombel wajib diisi.'];
    }
}
