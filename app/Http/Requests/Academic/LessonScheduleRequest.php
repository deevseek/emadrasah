<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('teaching-journals.manage') && $this->user()->can('teaching-journals.view-all');
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['required', 'integer', Rule::exists('semesters', 'id')->where('academic_year_id', $this->integer('academic_year_id'))],
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where('academic_year_id', $this->integer('academic_year_id'))->where('is_active', true)],
            'academic_subject_id' => ['required', Rule::exists('academic_subjects', 'id')->where('is_active', true)],
            'personnel_id' => ['required', Rule::exists('personnel', 'id')->where('is_active', true)],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'integer' => ':attribute harus berupa angka.', 'exists' => ':attribute tidak aktif atau tidak sesuai periode yang dipilih.', 'between' => ':attribute harus antara :min dan :max.', 'date_format' => 'Format :attribute harus :format.', 'after' => ':attribute harus setelah :date.'];
    }

    public function attributes(): array
    {
        return ['academic_year_id' => 'tahun ajaran', 'semester_id' => 'semester', 'classroom_id' => 'rombel', 'academic_subject_id' => 'mata pelajaran', 'personnel_id' => 'guru', 'day_of_week' => 'hari', 'start_time' => 'jam mulai', 'end_time' => 'jam selesai'];
    }
}
