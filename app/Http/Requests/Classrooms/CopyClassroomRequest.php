<?php
declare(strict_types=1); namespace App\Http\Requests\Classrooms; use Illuminate\Foundation\Http\FormRequest;
class CopyClassroomRequest extends FormRequest {public function authorize():bool{return $this->user()->can('classrooms.copy-structure');}public function rules():array{return ['source_academic_year_id'=>['required','exists:academic_years,id','different:target_academic_year_id'],'target_academic_year_id'=>['required','exists:academic_years,id'],'classroom_ids'=>['required','array','min:1'],'classroom_ids.*'=>['exists:classrooms,id'],'copy_homeroom'=>['boolean']];}}
