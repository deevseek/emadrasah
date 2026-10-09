<?php

declare(strict_types=1);

namespace App\Http\Requests\Rfid;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('rfid_device');
    }

    public function rules(): array
    {
        return [
            'firmware_version' => ['required', 'string', 'max:50'],
            'ip' => ['nullable', 'ip'],
            'rssi' => ['nullable', 'integer', 'between:-120,0'],
            'mode' => ['required', 'in:reader,writer,idle,writing'],
            'mac_address' => ['nullable', 'mac_address'],
            'wifi_bssid' => ['nullable', 'mac_address'],
            'wifi_gateway' => ['nullable', 'ipv4'],
            'wifi_subnet' => ['nullable', 'ipv4'],
            'wifi_channel' => ['nullable', 'integer', 'between:1,14'],
            'free_heap' => ['nullable', 'integer', 'between:0,4294967295'],
            'wifi_disconnect_count' => ['nullable', 'integer', 'between:0,4294967295'],
            'wifi_disconnect_reason' => ['nullable', 'integer', 'between:0,65535'],
        ];
    }
}
