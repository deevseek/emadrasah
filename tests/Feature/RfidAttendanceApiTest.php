<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\RfidDevice;
use App\Services\Academic\RfidAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery\MockInterface;
use Tests\TestCase;

class RfidAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = [
        'card_token' => '7142C511E9A163679A43E2259A723517',
        'uid' => 'E797BC64',
    ];

    public function test_post_is_recognized_and_enters_rfid_authentication_middleware(): void
    {
        $this->postJson('/api/rfid/attendance', self::PAYLOAD)
            ->assertStatus(401)
            ->assertJsonPath('code', 'DEVICE_UNAUTHORIZED')
            ->assertHeaderMissing('Location');
    }

    public function test_authenticated_json_post_reaches_the_form_request_and_service(): void
    {
        $token = 'token-reader-test-yang-tidak-rahasia';
        $device = RfidDevice::create([
            'device_id' => 'reader-test-01',
            'name' => 'Reader Test',
            'device_type' => 'reader',
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->mock(RfidAttendanceService::class, function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('record')
                ->once()
                ->with(self::PAYLOAD['card_token'], self::PAYLOAD['uid'], \Mockery::on(
                    fn (RfidDevice $authenticated): bool => $authenticated->is($device),
                ))
                ->andReturn(['http' => 201, 'success' => true, 'message' => 'Absensi berhasil']);
        });

        $this->withHeaders([
            'X-Device-Id' => $device->device_id,
            'X-Device-Token' => $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/rfid/attendance', self::PAYLOAD)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertHeaderMissing('Location');
    }

    public function test_get_remains_method_not_allowed_instead_of_a_false_not_found(): void
    {
        $this->getJson('/api/rfid/attendance')
            ->assertStatus(405)
            ->assertHeaderMissing('Location');
    }

    public function test_post_does_not_redirect_even_when_proxy_headers_are_present(): void
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'mimuslimatnudemak.sch.id',
        ])->postJson('/api/rfid/attendance', self::PAYLOAD)
            ->assertStatus(401)
            ->assertJsonPath('code', 'DEVICE_UNAUTHORIZED')
            ->assertHeaderMissing('Location');
    }

    public function test_post_route_is_written_to_the_route_cache(): void
    {
        try {
            $this->assertSame(0, Artisan::call('route:cache'), Artisan::output());

            $cacheFiles = glob(base_path('bootstrap/cache/routes-*.php')) ?: [];
            $this->assertNotEmpty($cacheFiles);
            $cache = file_get_contents($cacheFiles[0]);

            $this->assertIsString($cache);
            $this->assertStringContainsString('api/rfid/attendance', $cache);
            $this->assertStringContainsString("'POST'", $cache);
        } finally {
            Artisan::call('route:clear');
        }
    }
}
