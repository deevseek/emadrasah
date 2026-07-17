<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceVerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EmployeeAttendanceVerifyRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employee-attendances.verify') ?? false; }
    public function rules(): array { return ['verification_status'=>['required', Rule::enum(AttendanceVerificationStatus::class)], 'verification_notes'=>['nullable','string','max:1000']]; }
}
