<?php
declare(strict_types=1);namespace App\Http\Requests\Academic;use Illuminate\Foundation\Http\FormRequest;
class LiveAttendanceRequest extends FormRequest{public function authorize():bool{return $this->user()?->can('academic-attendance.view')===true;}public function rules():array{return ['classroom_id'=>['required','integer','exists:classrooms,id'],'attendance_date'=>['required','date_format:Y-m-d'],'cursor'=>['nullable','integer','min:0']];}}
