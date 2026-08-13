<?php
declare(strict_types=1);
namespace App\Http\Requests\Academic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SaveGradesRequest extends FormRequest { public function authorize():bool{return $this->user()->can('academic-grades.manage');} public function rules():array{return ['academic_year_id'=>['required','exists:academic_years,id'],'semester_id'=>['required',Rule::exists('semesters','id')->where('academic_year_id',$this->integer('academic_year_id'))],'classroom_id'=>['required','exists:classrooms,id'],'academic_subject_id'=>['required','exists:academic_subjects,id'],'grades'=>['required','array','min:1'],'grades.*.student_id'=>['required','distinct','exists:students,id'],'grades.*.score'=>['nullable','numeric','between:0,100'],'grades.*.notes'=>['nullable','string','max:1000']];} }
