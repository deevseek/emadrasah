<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeachingJournalRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('teaching-journals.manage') ?? false; }
    public function rules(): array
    {
        $year = $this->integer('academic_year_id');
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', Rule::exists('semesters', 'id')->where('academic_year_id', $year)],
            'classroom_id' => ['required', Rule::exists('classrooms', 'id')->where('academic_year_id', $year)->where('is_active', true)],
            'academic_subject_id' => ['required', Rule::exists('academic_subjects', 'id')->when(! $this->route('teaching_journal'), fn ($query) => $query->where('is_active', true))],
            'personnel_id' => ['nullable', 'integer'],
            'journal_date' => ['required', 'date'],
            'lesson_number' => ['required', 'string', 'max:50'],
            'topic' => ['required', 'string', 'max:5000'],
            'learning_objectives' => ['nullable', 'string', 'max:10000'],
            'learning_material' => ['nullable', 'string', 'max:10000'],
            'learning_method' => ['required', 'string', 'max:255'],
            'learning_activity' => ['nullable', 'string', 'max:10000'],
            'assignment' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'attendances.*.status' => ['required', Rule::in(['present', 'sick', 'permitted', 'absent'])],
            'attendances.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'topic.max' => 'Uraian mengajar tidak boleh lebih dari 5.000 karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'academic_year_id' => 'tahun ajaran',
            'semester_id' => 'semester',
            'classroom_id' => 'rombel',
            'academic_subject_id' => 'mata pelajaran',
            'personnel_id' => 'ustaz/ustazah',
            'journal_date' => 'hari/tanggal',
            'lesson_number' => 'jam ke',
            'topic' => 'uraian mengajar',
            'learning_objectives' => 'tujuan pembelajaran',
            'learning_material' => 'materi pembelajaran',
            'learning_method' => 'metode pembelajaran',
            'learning_activity' => 'kegiatan pembelajaran',
            'assignment' => 'tugas',
            'notes' => 'keterangan/catatan',
            'attendances' => 'absensi siswa',
            'attendances.*.student_id' => 'siswa',
            'attendances.*.status' => 'status kehadiran',
            'attendances.*.notes' => 'keterangan absensi',
        ];
    }
}
