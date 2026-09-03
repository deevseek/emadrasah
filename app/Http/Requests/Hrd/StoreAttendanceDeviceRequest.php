<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrd;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('personnel-attendance.manage-devices') === true;
    }

    public function rules(): array
    {
        return [
            'personnel_id' => ['required', 'integer', Rule::exists('personnel', 'id')->where('is_active', true)],
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
