<?php
declare(strict_types=1);
namespace App\Http\Requests\Academic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ClassroomJournalRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('classroom-journals.manage')??false;}
    public function rules():array{$year=(int)$this->input('academic_year_id');return ['academic_year_id'=>['required','exists:academic_years,id'],'semester_id'=>['required',Rule::exists('semesters','id')->where('academic_year_id',$year)],'classroom_id'=>['required',Rule::exists('classrooms','id')->where('academic_year_id',$year)->where('is_active',true)],'journal_date'=>['required','date'],'agenda'=>['nullable','string'],'classroom_condition'=>['nullable','string'],'student_discipline'=>['nullable','string'],'important_events'=>['nullable','string'],'teacher_notes'=>['nullable','string'],'follow_up'=>['nullable','string']];}
}
