<?php
declare(strict_types=1); namespace App\Http\Requests\Classrooms; use Illuminate\Foundation\Http\FormRequest;
class PromotionPreviewRequest extends FormRequest {public function authorize():bool{return $this->user()->can('classrooms.promote');}public function rules():array{return ['source_academic_year_id'=>['required','exists:academic_years,id','different:target_academic_year_id'],'target_academic_year_id'=>['required','exists:academic_years,id'],'plans'=>['nullable','array']];}}
