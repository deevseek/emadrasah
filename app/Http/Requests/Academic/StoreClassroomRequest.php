<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['academic_year_id'=>['required','exists:academic_years,id'],'grade_level_id'=>['required','exists:grade_levels,id'],'name'=>['required','string','max:100',Rule::unique('classrooms','name')->where('academic_year_id',$this->input('academic_year_id'))->where('grade_level_id',$this->input('grade_level_id'))],'code'=>['required','string','max:50',Rule::unique('classrooms','code')->where('academic_year_id',$this->input('academic_year_id'))],'capacity'=>['required','integer','min:1','max:60'],'room'=>['nullable','string','max:100'],'description'=>['nullable','string','max:1000'],'is_active'=>['boolean']];
    }
}
