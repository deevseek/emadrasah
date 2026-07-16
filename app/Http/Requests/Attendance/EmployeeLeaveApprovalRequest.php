<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class EmployeeLeaveApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('employee-leaves.approve') || $this->user()?->can('employee-leaves.reject')) ?? false;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rejection_reason' => 'alasan penolakan',
        ];
    }
}
