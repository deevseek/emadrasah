<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Models\{Personnel, PersonnelAttendanceDevice, User};
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceDeviceService
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function register(Personnel $personnel, array $data, User $actor): PersonnelAttendanceDevice
    {
        return DB::transaction(function () use ($personnel, $data, $actor): PersonnelAttendanceDevice {
            $hash = hash('sha256', strtolower($data['device_uuid']));
            $device = PersonnelAttendanceDevice::query()->where('personnel_id', $personnel->id)->where('device_uuid_hash', $hash)->lockForUpdate()->first();
            if (! $device && PersonnelAttendanceDevice::query()->where('personnel_id', $personnel->id)->whereNull('revoked_at')->count() >= (int) $this->settings->get('hrd_attendance_max_devices', 2)) {
                throw ValidationException::withMessages(['device_uuid' => 'Batas jumlah perangkat absensi aktif telah tercapai.']);
            }

            $alreadyTrusted = $device?->is_trusted === true && $device->revoked_at === null;
            $device ??= new PersonnelAttendanceDevice(['personnel_id' => $personnel->id, 'device_uuid_hash' => $hash]);
            $device->fill([
                'device_name' => $data['device_name'],
                'browser' => $data['browser'] ?? null,
                'platform' => $data['platform'] ?? null,
                'first_seen_at' => $device->first_seen_at ?? now(),
                'last_seen_at' => now(),
                'is_trusted' => $alreadyTrusted,
                'trusted_at' => $alreadyTrusted ? $device->trusted_at : null,
                'trusted_by' => $alreadyTrusted ? $device->trusted_by : null,
                'revoked_at' => null,
            ])->save();

            activity('hrd')->causedBy($actor)->performedOn($device)->log('Mengajukan perangkat absensi untuk divalidasi.');

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
