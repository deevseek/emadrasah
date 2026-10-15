<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\RfidDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RfidNetworkMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function device(string $id = 'reader-network', string $type = 'reader'): RfidDevice
    {
        return RfidDevice::create(['device_id' => $id, 'name' => $id, 'device_type' => $type,
            'token_hash' => hash('sha256', 'test-'.$id), 'is_active' => true]);
    }

    private function headers(RfidDevice $device): array
    {
        return ['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'test-'.$device->device_id];
    }

    private function heartbeat(array $extra = []): array
    {
        return $extra + ['firmware_version' => '2.3.2-esp8266-buzzer', 'ip' => '192.168.0.157', 'rssi' => -89, 'mode' => 'reader'];
    }

    public function test_old_reader_and_idle_writer_heartbeat_remain_compatible(): void
    {
        foreach (['reader' => 'reader', 'writer' => 'idle'] as $type => $mode) {
            $device = $this->device($type.'-legacy', $type);
            $this->withHeaders($this->headers($device))->postJson('/api/rfid/device/heartbeat', $this->heartbeat(['mode' => $mode]))
                ->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['server_time']);
            $device->refresh();
            $this->assertTrue($device->isOnline());
            $this->assertNotNull($device->last_heartbeat_at);
            $this->assertNull($device->mac_address);
            $this->assertSame($type, $device->device_type->value);
        }
    }

    public function test_new_diagnostics_are_saved_and_omitted_fields_preserve_last_report(): void
    {
        $device = $this->device();
        $telemetry = ['firmware_version' => '2.4.2', 'mac_address' => 'aa:bb:cc:dd:ee:01',
            'wifi_bssid' => 'aa:bb:cc:dd:ff:01', 'wifi_gateway' => '192.168.0.1', 'wifi_subnet' => '255.255.255.0',
            'wifi_channel' => 6, 'free_heap' => 28000, 'wifi_disconnect_count' => 4, 'wifi_disconnect_reason' => 201];
        $this->withHeaders($this->headers($device))->postJson('/api/rfid/device/heartbeat', $this->heartbeat($telemetry))->assertOk();
        $this->assertDatabaseHas('rfid_devices', ['id' => $device->id, 'mac_address' => 'AA:BB:CC:DD:EE:01',
            'wifi_bssid' => 'AA:BB:CC:DD:FF:01', 'wifi_gateway' => '192.168.0.1', 'wifi_subnet' => '255.255.255.0',
            'wifi_channel' => 6, 'free_heap' => 28000, 'wifi_disconnect_count' => 4, 'wifi_disconnect_reason' => 201]);
        $this->withHeaders($this->headers($device))->postJson('/api/rfid/device/heartbeat', $this->heartbeat())->assertOk();
        $this->assertDatabaseHas('rfid_devices', ['id' => $device->id, 'mac_address' => 'AA:BB:CC:DD:EE:01']);
        $this->withHeaders($this->headers($device))->postJson('/api/rfid/device/heartbeat', $this->heartbeat(['mac_address' => null, 'free_heap' => null]))->assertOk();
        $this->assertNull($device->fresh()->mac_address);
        $this->assertNull($device->fresh()->free_heap);
        $this->assertDatabaseCount('rfid_device_mac_observations', 1);
    }

    public static function invalidTelemetry(): array
    {
        return [['mac_address', 'bad'], ['wifi_bssid', 'bad'], ['wifi_gateway', 'bad'], ['wifi_subnet', 'bad'],
            ['wifi_channel', 15], ['free_heap', -1], ['wifi_disconnect_count', 4294967296], ['wifi_disconnect_reason', 65536]];
    }

    #[DataProvider('invalidTelemetry')]
    public function test_invalid_telemetry_returns_json_without_marking_a_valid_heartbeat(string $field, mixed $value): void
    {
        $device = $this->device();
        $this->withHeaders($this->headers($device))->post('/api/rfid/device/heartbeat', $this->heartbeat([$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field)->assertHeaderMissing('Location');
        $this->assertNull($device->fresh()->last_heartbeat_at);
        $this->assertNotNull($device->fresh()->last_seen_at);
        $this->assertDatabaseCount('rfid_device_mac_observations', 0);
    }

    public function test_bad_token_and_inactive_device_are_rejected_without_touching_last_seen(): void
    {
        $device = $this->device();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'wrong'])
            ->postJson('/api/rfid/device/heartbeat', $this->heartbeat())->assertUnauthorized();
        $device->update(['is_active' => false]);
        $this->withHeaders($this->headers($device))->postJson('/api/rfid/device/heartbeat', $this->heartbeat())->assertUnauthorized();
        $this->assertNull($device->fresh()->last_seen_at);
    }

    public function test_additive_migrations_can_roll_back_and_reapply_without_changing_existing_devices(): void
    {
        $device = $this->device();
        $migration = require database_path('migrations/2026_10_09_000000_add_rfid_network_diagnostics.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('rfid_devices', 'last_heartbeat_at'));
        $this->assertDatabaseHas('rfid_devices', ['id' => $device->id, 'device_id' => $device->device_id, 'token_hash' => $device->token_hash]);
        $migration->up();
        $this->assertNull($device->fresh()->last_heartbeat_at);
        $requests = require database_path('migrations/2026_10_09_000001_create_rfid_attendance_requests.php');
        $requests->down();
        $requests->up();
        $this->assertDatabaseCount('rfid_devices', 1);
        $this->assertDatabaseCount('rfid_attendance_requests', 0);
    }

    public function test_status_uses_last_authenticated_request_and_never_claims_physical_wifi(): void
    {
        $this->travelTo(now()->startOfSecond());
        $device = $this->device();
        $this->assertFalse($device->isOnline());
        $device->update(['last_seen_at' => now()->subSeconds(75)]);
        $this->assertTrue($device->isOnline());
        $this->travel(1)->seconds();
        $this->assertFalse($device->fresh()->isOnline());
        $this->withHeaders($this->headers($device))->getJson('/api/rfid/device/command')->assertOk();
        $this->assertTrue($device->fresh()->isOnline());
        $this->assertNull($device->fresh()->last_heartbeat_at);
        $device->update(['is_active' => false]);
        $this->assertFalse($device->isOnline());
    }

    public function test_rate_limit_is_per_authenticated_device_even_behind_same_nat(): void
    {
        $first = $this->device('nat-reader-1');
        $second = $this->device('nat-reader-2');
        for ($i = 0; $i < 120; $i++) {
            $this->withHeaders($this->headers($first))->getJson('/api/rfid/device/command')->assertOk();
        }
        $this->withHeaders($this->headers($first))->postJson('/api/rfid/device/heartbeat', $this->heartbeat())
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->withHeaders($this->headers($second))->postJson('/api/rfid/device/heartbeat', $this->heartbeat())->assertOk();
    }

    public function test_audit_is_read_only_and_marks_duplicate_ip_and_multiple_macs_as_suspicions(): void
    {
        $first = $this->device('reader-04');
        $second = $this->device('reader-05');
        foreach ([$first, $second] as $index => $device) {
            $this->withHeaders($this->headers($device))->postJson('/api/rfid/device/heartbeat', $this->heartbeat([
                'mac_address' => 'AA:BB:CC:DD:EE:0'.($index + 1),
                'wifi_gateway' => $index ? '192.168.0.254' : '192.168.0.1',
                'wifi_bssid' => 'AA:BB:CC:DD:FF:0'.($index + 1),
            ]))->assertOk();
        }
        $this->withHeaders($this->headers($first))->postJson('/api/rfid/device/heartbeat', $this->heartbeat(['mac_address' => 'AA:BB:CC:DD:EE:03']))->assertOk();
        $before = DB::table('rfid_devices')->orderBy('id')->get()->toJson();
        $macBefore = DB::table('rfid_device_mac_observations')->orderBy('id')->get()->toJson();
        $this->assertSame(0, Artisan::call('rfid:network-audit'));
        $output = Artisan::output();
        foreach (['reader-04', 'dugaan konflik yang perlu diverifikasi pada DHCP/ARP router',
            'bukan kepastian konflik IP', 'LAN berbeda dapat memakai IP sama', 'lebih dari satu MAC'] as $message) {
            $this->assertStringContainsString($message, $output);
        }
        $this->assertStringNotContainsString('test-reader-04', $output);
        $this->assertStringNotContainsString($first->token_hash, $output);
        $this->assertSame($before, DB::table('rfid_devices')->orderBy('id')->get()->toJson());
        $this->assertSame($macBefore, DB::table('rfid_device_mac_observations')->orderBy('id')->get()->toJson());
    }
}
