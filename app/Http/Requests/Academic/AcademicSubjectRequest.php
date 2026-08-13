<?php
declare(strict_types=1);
namespace App\Http\Requests\Academic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class AcademicSubjectRequest extends FormRequest { public function authorize():bool{return $this->user()?->can('academic-subjects.manage')??false;} public function rules():array{return ['code'=>['nullable','string','max:30',Rule::unique('academic_subjects','code')->ignore($this->route('subject'))],'name'=>['required','string','max:255'],'is_active'=>['sometimes','boolean'],'sort_order'=>['required','integer','min:0','max:9999']];} }
