<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class EmployeeCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee-attendances.check-out') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
