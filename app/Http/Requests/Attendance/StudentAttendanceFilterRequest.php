<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StudentAttendanceFilterRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canAny(['student-attendances.view','student-attendances.view-own-class','student-attendances.report']) ?? false; }
    public function rules(): array { return ['from'=>['nullable','date'],'to'=>['nullable','date'],'date'=>['nullable','date'],'month'=>['nullable','date_format:Y-m'],'classroom_id'=>['nullable','integer'],'student_id'=>['nullable','integer'],'status'=>['nullable','string'],'q'=>['nullable','string','max:100']]; }
}
