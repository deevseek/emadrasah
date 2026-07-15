<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;
use App\Models\Employee; use App\Models\Semester; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class TeachingAssignmentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('teaching_assignment')?->id; return ['academic_year_id'=>['required','exists:academic_years,id'],'semester_id'=>['required','exists:semesters,id',fn($a,$v,$f)=>$v && Semester::find($v)?->academic_year_id != $this->input('academic_year_id') ? $f('Semester harus sesuai tahun ajaran.') : null],'employee_id'=>['required','exists:employees,id',fn($a,$v,$f)=>$v && !Employee::whereKey($v)->where('is_active',true)->exists() ? $f('Pegawai harus aktif.') : null,Rule::unique('teaching_assignments','employee_id')->where('semester_id',$this->input('semester_id'))->where('classroom_id',$this->input('classroom_id'))->where('subject_id',$this->input('subject_id'))->ignore($id)],'classroom_id'=>['required','exists:classrooms,id'],'subject_id'=>['required','exists:subjects,id'],'weekly_hours'=>['nullable','integer','min:1','max:60'],'is_active'=>['nullable','boolean']]; } }
