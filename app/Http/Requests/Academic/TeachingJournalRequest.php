<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Services\Academic\JournalAttendanceResolver;
use App\Services\Academic\TeachingJournalAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeachingJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }
        app(TeachingJournalAccessService::class)->ensureManage($this->user(), $this->route('teaching_journal'));

        return true;
    }

    public function rules(): array
    {
        $year = $this->integer('academic_year_id');
        $openingLesson = app(JournalAttendanceResolver::class)->isOpeningLesson($this->string('lesson_number')->toString());

        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester_id' => ['required', 'integer', Rule::exists('semesters', 'id')->where('academic_year_id', $year)],
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where('academic_year_id', $year)],
            'academic_subject_id' => ['required', 'integer', Rule::exists('academic_subjects', 'id')],
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
            'attendances' => [$openingLesson ? 'required' : 'prohibited', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'attendances.*.status' => ['required', Rule::in(['present', 'sick', 'permitted', 'absent', 'pending'])],
            'attendances.*.mode' => ['nullable', Rule::in(['daily', 'manual', 'keep'])],
            'attendances.*.notes' => ['nullable', 'string', 'max:1000'],
            'attendance_corrections' => [$openingLesson ? 'prohibited' : 'nullable', 'array'],
            'attendance_corrections.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'attendance_corrections.*.status' => ['nullable', Rule::in(['present', 'sick', 'permitted', 'absent'])],
            'attendance_corrections.*.reason' => ['nullable', 'required_with:attendance_corrections.*.status', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'exists' => ':attribute yang dipilih tidak valid atau tidak sesuai periode.',
            'integer' => ':attribute harus berupa angka bulat.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute tidak boleh lebih dari :max karakter.',
            'array' => ':attribute harus berupa daftar.',
            'min.array' => ':attribute wajib berisi minimal :min data.',
            'in' => ':attribute yang dipilih tidak valid.',
            'distinct' => ':attribute tidak boleh diisi berulang.',
            'prohibited' => ':attribute tidak boleh diisi pada jam pelajaran ini.',
            'required_with' => ':attribute wajib diisi apabila koreksi diberikan.',
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
            'attendance_corrections' => 'koreksi absensi khusus pelajaran',
            'attendance_corrections.*.student_id' => 'siswa yang dikoreksi',
            'attendance_corrections.*.status' => 'status koreksi',
            'attendance_corrections.*.reason' => 'alasan koreksi',
        ];
    }
}
