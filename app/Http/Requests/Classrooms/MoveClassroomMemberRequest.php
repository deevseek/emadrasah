<?php
declare(strict_types=1); namespace App\Http\Requests\Classrooms; use Illuminate\Foundation\Http\FormRequest;
class MoveClassroomMemberRequest extends FormRequest {public function authorize():bool{return $this->user()->can('classrooms.manage-students');}public function rules():array{return ['target_classroom_id'=>['required','exists:classrooms,id'],'moved_at'=>['required','date'],'notes'=>['nullable','max:1000']];}}
