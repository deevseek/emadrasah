<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RfidDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RfidNetworkAudit extends Command
{
    protected $signature = 'rfid:network-audit';

    protected $description = 'Audit read-only telemetri jaringan RFID tanpa mengubah perangkat atau token';

    public function handle(): int
    {
        $devices = RfidDevice::query()->orderBy('device_id')->get([
            'id', 'device_id', 'ip_address', 'mac_address', 'wifi_gateway',
            'wifi_subnet', 'wifi_bssid', 'rssi', 'firmware_version', 'last_heartbeat_at',
        ]);
        $this->table(['Device ID', 'IP', 'MAC', 'Gateway', 'Subnet', 'BSSID', 'RSSI', 'Firmware', 'Heartbeat terakhir'],
            $devices->map(fn (RfidDevice $device): array => [
                $device->device_id, $device->ip_address ?? '—', $device->mac_address ?? '—',
                $device->wifi_gateway ?? '—', $device->wifi_subnet ?? '—', $device->wifi_bssid ?? '—',
                $device->rssi === null ? '—' : $device->rssi.' dBm', $device->firmware_version ?? '—',
                $device->last_heartbeat_at?->toIso8601String() ?? 'Belum tercatat',
            ])->all());
        $this->info('Data adalah laporan terakhir perangkat, bukan pemeriksaan WiFi fisik. Heartbeat lama/absen tidak membuktikan WiFi putus.');
        $duplicates = $devices->filter(fn (RfidDevice $device): bool => filled($device->ip_address))
            ->groupBy('ip_address')->filter(fn ($group): bool => $group->count() > 1);
        foreach ($duplicates as $ip => $group) {
            $this->warn('IP sama '.$ip.': '.$group->pluck('device_id')->implode(', ').
                ' — dugaan konflik yang perlu diverifikasi pada DHCP/ARP router; bukan kepastian konflik IP.');
        }
        if ($duplicates->isEmpty()) {
            $this->info('Tidak ada IP duplikat dalam laporan terakhir.');
        }
        $this->line('LAN berbeda dapat memakai IP sama. Gateway/subnet/BSSID membantu pemeriksaan, tetapi tidak membuktikan satu LAN.');

        $observations = DB::table('rfid_device_mac_observations')->orderBy('mac_address')->get()
            ->groupBy('rfid_device_id');
        foreach ($devices as $device) {
            $macs = ($observations->get($device->id) ?? collect())->pluck('mac_address')
                ->merge($device->mac_address ? [$device->mac_address] : [])->unique();
            if ($macs->count() > 1) {
                $this->warn('Device ID '.$device->device_id.' pernah dilaporkan oleh lebih dari satu MAC: '.$macs->implode(', ').
                    '. Verifikasi identitas perangkat; riwayat juga bisa berasal dari penggantian hardware.');
            }
        }

        return self::SUCCESS;
    }
}
