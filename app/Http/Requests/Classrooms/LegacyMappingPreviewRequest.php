<?php
declare(strict_types=1); namespace App\Http\Requests\Classrooms; use Illuminate\Foundation\Http\FormRequest;
class LegacyMappingPreviewRequest extends FormRequest {public function authorize():bool{return $this->user()->can('classrooms.map-legacy');}public function rules():array{return ['academic_year_id'=>['required','exists:academic_years,id'],'legacy_label'=>['required','max:255'],'classroom_id'=>['required','exists:classrooms,id']];}}
