<?php
declare(strict_types=1); namespace App\Http\Requests\Classrooms; use Illuminate\Foundation\Http\FormRequest;
class AddClassroomMembersRequest extends FormRequest {public function authorize():bool{return $this->user()->can('classrooms.manage-students');}public function rules():array{return ['student_ids'=>['required','array','min:1'],'student_ids.*'=>['integer','distinct','exists:students,id']];}public function messages():array{return ['student_ids.required'=>'Pilih sekurangnya satu siswa.'];}}
