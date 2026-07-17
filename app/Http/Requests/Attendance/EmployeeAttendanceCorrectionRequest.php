<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EmployeeAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employee-attendances.correct') ?? false; }
    public function rules(): array { return ['checked_in_at'=>['nullable','date_format:Y-m-d\TH:i'], 'checked_out_at'=>['nullable','date_format:Y-m-d\TH:i'], 'status'=>['required', Rule::enum(AttendanceStatus::class)], 'notes'=>['nullable','string','max:2000'], 'reason'=>['required','string','max:2000']]; }
}
