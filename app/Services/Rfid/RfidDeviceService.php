<?php

declare(strict_types=1);

namespace App\Services\Rfid;

use App\Models\RfidDevice;
use App\Models\User;
use Illuminate\Support\Str;

class RfidDeviceService
{
    /** @return array{device:RfidDevice,token:string} */
    public function create(array $data, User $actor): array
    {
        $token = Str::random(64);
        $device = RfidDevice::create([
            ...$data,
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);
        activity('rfid-device')->causedBy($actor)->performedOn($device)->log('Mendaftarkan perangkat RFID.');

        return compact('device', 'token');
    }

    public function rotateToken(RfidDevice $device, User $actor): string
    {
        $token = Str::random(64);
        $device->update(['token_hash' => hash('sha256', $token), 'last_seen_at' => null]);
        activity('rfid-device')->causedBy($actor)->performedOn($device)->log('Mengganti token perangkat RFID.');

        return $token;
    }

    public function toggle(RfidDevice $device, User $actor): void
    {
        $device->update(['is_active' => ! $device->is_active]);
        activity('rfid-device')->causedBy($actor)->performedOn($device)->withProperties(['is_active' => $device->is_active])->log('Mengubah status perangkat RFID.');
    }
}
