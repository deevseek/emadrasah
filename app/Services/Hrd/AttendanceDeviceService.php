<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Models\{PersonnelAttendanceDevice, User};
use Illuminate\Support\Facades\DB;

class AttendanceDeviceService
{
    public function register(array $data, User $actor): PersonnelAttendanceDevice
    {
        return DB::transaction(function () use ($data, $actor): PersonnelAttendanceDevice {
            $device = PersonnelAttendanceDevice::query()->updateOrCreate([
                'personnel_id' => $data['personnel_id'],
                'device_uuid_hash' => hash('sha256', strtolower($data['device_uuid'])),
            ], [
                'device_name' => $data['device_name'],
                'browser' => $data['browser'] ?? null,
                'platform' => $data['platform'] ?? null,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'is_trusted' => true,
                'trusted_at' => now(),
                'trusted_by' => $actor->id,
                'revoked_at' => null,
            ]);

            activity('hrd')->causedBy($actor)->performedOn($device)->log('Mendaftarkan perangkat absensi personalia.');

            return $device;
        }, 3);
    }

    public function approve(PersonnelAttendanceDevice $device, User $actor): void
    {
        DB::transaction(function () use ($device, $actor): void {
            $device->update(['is_trusted' => true, 'trusted_at' => now(), 'trusted_by' => $actor->id, 'revoked_at' => null]);
            activity('hrd')->causedBy($actor)->performedOn($device)->log('Menyetujui perangkat absensi personalia.');
        }, 3);
    }

    public function revoke(PersonnelAttendanceDevice $device, User $actor): void
    {
        DB::transaction(function () use ($device, $actor): void {
            $device->update(['is_trusted' => false, 'revoked_at' => now()]);
            activity('hrd')->causedBy($actor)->performedOn($device)->log('Mencabut perangkat absensi personalia.');
        }, 3);
    }
}
