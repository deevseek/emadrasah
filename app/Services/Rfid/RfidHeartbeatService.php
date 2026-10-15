<?php

declare(strict_types=1);

namespace App\Services\Rfid;

use App\Models\RfidDevice;
use Illuminate\Support\Facades\DB;

class RfidHeartbeatService
{
    public function record(RfidDevice $device, array $data): void
    {
        DB::transaction(function () use ($device, $data): void {
            $device = RfidDevice::query()->lockForUpdate()->findOrFail($device->id);
            $time = now();
            foreach (['mac_address', 'wifi_bssid'] as $field) {
                if (isset($data[$field])) {
                    $data[$field] = strtoupper(str_replace('-', ':', $data[$field]));
                }
            }
            if (isset($data['mac_address'])) {
                DB::table('rfid_device_mac_observations')->upsert([
                    'rfid_device_id' => $device->id,
                    'mac_address' => $data['mac_address'],
                    'first_seen_at' => $time,
                    'last_seen_at' => $time,
                ], ['rfid_device_id', 'mac_address'], ['last_seen_at']);
            }
            $data['ip_address'] = $data['ip'] ?? null;
            unset($data['ip']);
            $device->update($data + [
                'rssi' => null,
                'last_seen_at' => $time,
                'last_heartbeat_at' => $time,
            ]);
        }, 3);
    }
}
