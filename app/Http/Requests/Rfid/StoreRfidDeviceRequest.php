<?php

declare(strict_types=1);

namespace App\Http\Requests\Rfid;

use App\Enums\RfidDeviceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRfidDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rfid-device.manage') === true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9_-]*$/', 'unique:rfid_devices,device_id'],
            'name' => ['required', 'string', 'max:150'],
            'device_type' => ['required', Rule::enum(RfidDeviceType::class)],
        ];
    }

    public function messages(): array
    {
        return ['device_id.regex' => 'ID perangkat hanya boleh berisi huruf kecil, angka, tanda hubung, dan garis bawah.'];
    }
}
