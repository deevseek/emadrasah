<?php
declare(strict_types=1); namespace App\Http\Requests\Classrooms; use Illuminate\Foundation\Http\FormRequest;
class UpdateClassroomHomeroomRequest extends FormRequest {public function authorize():bool{return $this->user()->can('classrooms.assign-homeroom');}public function rules():array{return ['homeroom_personnel_id'=>['nullable','exists:personnel,id']];}}
