<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrd;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('personnel-attendance.register-device') === true;
    }

    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'uuid'],
            'device_name' => ['required', 'string', 'max:100'],
            'browser' => ['nullable', 'string', 'max:80'],
            'platform' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return ['device_uuid.uuid' => 'UUID perangkat harus menggunakan format UUID yang valid.'];
    }
}
