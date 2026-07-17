<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\StudentAttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('student-attendances.correct') ?? false; }
    public function rules(): array { return ['status'=>['required',Rule::in(array_column(StudentAttendanceStatus::cases(),'value'))],'arrival_time'=>['nullable','date_format:H:i'],'departure_time'=>['nullable','date_format:H:i'],'late_minutes'=>['nullable','integer','min:0','max:600'],'early_leave_minutes'=>['nullable','integer','min:0','max:600'],'reason'=>['nullable','string','max:1000'],'notes'=>['nullable','string','max:1000'],'correction_reason'=>['required','string','min:5','max:1000']]; }
}
