<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class EmployeeCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee-attendances.check-in') ?? false;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'integer', 'min:0'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'selfie' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
