<?php
declare(strict_types=1);
namespace App\Http\Requests\Academic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class TeachingJournalRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('teaching-journals.manage')??false;}
    public function rules():array
    {
        $year=(int)$this->input('academic_year_id');
        return ['academic_year_id'=>['required','exists:academic_years,id'],'semester_id'=>['required',Rule::exists('semesters','id')->where('academic_year_id',$year)],'classroom_id'=>['required',Rule::exists('classrooms','id')->where('academic_year_id',$year)->where('is_active',true)],'academic_subject_id'=>['required',Rule::exists('academic_subjects','id')->when(!$this->route('teaching_journal'),fn($q)=>$q->where('is_active',true))],'personnel_id'=>['nullable','integer'],'journal_date'=>['required','date'],'lesson_number'=>['nullable','string','max:50'],'topic'=>['required','string','max:255'],'learning_objectives'=>['nullable','string'],'learning_material'=>['nullable','string'],'learning_method'=>['nullable','string','max:255'],'learning_activity'=>['nullable','string'],'assignment'=>['nullable','string'],'notes'=>['nullable','string']];
    }
}
