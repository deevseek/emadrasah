<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Models\{Classroom, Employee, Semester, Subject, TeachingAssignment};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeachingAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can($this->route('teachingAssignment') ? 'teaching-assignments.update' : 'teaching-assignments.create') ?? false; }
    public function rules(): array
    {
        return [
            'academic_year_id'=>['required','exists:academic_years,id'],
            'semester_id'=>['required','exists:semesters,id',fn($a,$v,$f)=>$v && Semester::find($v)?->academic_year_id != $this->input('academic_year_id') ? $f('Semester harus sesuai tahun ajaran.') : null],
            'employee_id'=>['required','exists:employees,id',fn($a,$v,$f)=>$v && !Employee::whereKey($v)->where('is_active',true)->exists() ? $f('Guru Pengampu harus aktif.') : null],
            'classroom_id'=>['required','exists:classrooms,id',fn($a,$v,$f)=>$this->invalidClassroom((int)$v,$f)],
            'subject_id'=>['required','exists:subjects,id',fn($a,$v,$f)=>$this->invalidSubject((int)$v,$f)],
            'weekly_hours'=>['required','integer','min:1','max:60'], 'starts_on'=>['nullable','date'], 'ends_on'=>['nullable','date','after_or_equal:starts_on'], 'notes'=>['nullable','string'], 'is_active'=>['nullable','boolean'],
        ];
    }
    private function invalidClassroom(int $id, callable $fail): void { $c=Classroom::find($id); if($c && (! $c->is_active || (int)$c->academic_year_id !== (int)$this->input('academic_year_id'))) $fail('Kelas harus aktif dan sesuai tahun ajaran.'); }
    private function invalidSubject(int $id, callable $fail): void { $s=Subject::with('gradeLevels')->find($id); $c=Classroom::with('gradeLevel')->find($this->input('classroom_id')); if($s && ! $s->is_active) $fail('Mata Pelajaran nonaktif tidak dapat digunakan.'); if($s && $c && $s->gradeLevels->isNotEmpty() && ! $s->gradeLevels->contains($c->grade_level_id)) $fail('Mata Pelajaran tidak sesuai tingkat kelas.'); }
}
