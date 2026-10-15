<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\RfidDevice;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidDeviceLiveStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_expires_and_recovers_after_heartbeat_without_exposing_credentials(): void
    {
        $this->seed(AccessControlSeeder::class);
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->assignRole('operator');
        $this->travelTo(now()->startOfSecond());
        $device = RfidDevice::create([
            'device_id' => 'reader-live', 'name' => 'Reader Live', 'device_type' => 'reader',
            'token_hash' => hash('sha256', 'live-token'), 'is_active' => true, 'last_seen_at' => now(),
        ]);
        $this->actingAs($operator)->get(route('rfid-devices.index'))->assertOk()
            ->assertSee('data-device-connection', false)->assertSee('perangkat-rfid/live', false);
        $this->getJson(route('rfid-devices.live'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('devices.0.online', true)->assertJsonPath('online_count', 1)
            ->assertDontSee($device->token_hash)->assertDontSee('live-token');
        $this->travel(76)->seconds();
        $this->getJson(route('rfid-devices.live'))->assertJsonPath('devices.0.online', false)
            ->assertJsonPath('online_count', 0);
        // Reading the dashboard must not refresh the device's presence.
        $this->assertFalse($device->fresh()->isOnline());
        $this->withHeaders(['X-Device-ID' => 'reader-live', 'X-Device-Token' => 'live-token'])
            ->postJson('/api/rfid/device/heartbeat', ['firmware_version' => '1.0', 'mode' => 'reader'])
            ->assertOk();
        $this->getJson(route('rfid-devices.live'))->assertJsonPath('devices.0.online', true);
        $device->update(['is_active' => false]);
        $this->getJson(route('rfid-devices.live'))->assertJsonPath('devices.0.online', false)
            ->assertJsonPath('devices.0.is_active', false);
    }

    public function test_status_requires_authentication_and_device_management_permission(): void
    {
        $this->getJson(route('rfid-devices.live'))->assertUnauthorized();
        $this->actingAs(User::factory()->create(['must_change_password' => false]))
            ->getJson(route('rfid-devices.live'))->assertForbidden();
    }

    public function test_empty_device_list_is_supported(): void
    {
        $this->seed(AccessControlSeeder::class);
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->assignRole('operator');
        $this->actingAs($operator)->getJson(route('rfid-devices.live'))->assertOk()
            ->assertExactJson(['devices' => [], 'online_count' => 0]);
    }
}
