<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Personnel, PersonnelAttendanceDevice, User};
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hrd_role_can_open_device_management_and_register_a_device(): void
    {
        $this->seed(AccessControlSeeder::class);
        $hrd = User::factory()->create(['must_change_password' => false]);
        $hrd->assignRole('hrd');
        $personnel = $this->personnel();
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->actingAs($hrd)->get(route('hrd.attendance-devices.index'))
            ->assertOk()
            ->assertSee('Manajemen Perangkat Absensi');

        $this->actingAs($hrd)->post(route('hrd.attendance-devices.store'), [
            'personnel_id' => $personnel->id,
            'device_uuid' => $uuid,
            'device_name' => 'Ponsel Ustaz Ahmad',
            'platform' => 'Android',
            'browser' => 'Chrome',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('personnel_attendance_devices', [
            'personnel_id' => $personnel->id,
            'device_uuid_hash' => hash('sha256', $uuid),
            'is_trusted' => true,
            'trusted_by' => $hrd->id,
            'revoked_at' => null,
        ]);
        $this->assertDatabaseMissing('personnel_attendance_devices', ['device_uuid_hash' => $uuid]);
    }

    public function test_hrd_can_approve_and_revoke_a_pending_device(): void
    {
        $this->seed(AccessControlSeeder::class);
        $hrd = User::factory()->create(['must_change_password' => false]);
        $hrd->assignRole('hrd');
        $device = PersonnelAttendanceDevice::create([
            'personnel_id' => $this->personnel()->id,
            'device_uuid_hash' => hash('sha256', 'pending-device'),
            'device_name' => 'Ponsel Baru',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'is_trusted' => false,
        ]);

        $this->actingAs($hrd)->patch(route('hrd.attendance-devices.approve', $device))->assertRedirect();
        $this->assertTrue($device->refresh()->is_trusted);
        $this->assertSame($hrd->id, $device->trusted_by);

        $this->actingAs($hrd)->patch(route('hrd.attendance-devices.revoke', $device))->assertRedirect();
        $this->assertFalse($device->refresh()->is_trusted);
        $this->assertNotNull($device->revoked_at);
    }

    public function test_teacher_cannot_access_device_management(): void
    {
        $this->seed(AccessControlSeeder::class);
        $teacher = User::factory()->create(['must_change_password' => false]);
        $teacher->assignRole('guru');

        $this->actingAs($teacher)->get(route('hrd.attendance-devices.index'))->assertForbidden();
    }

    private function personnel(): Personnel
    {
        return Personnel::create([
            'full_name' => 'Ustaz Ahmad',
            'gender' => 'male',
            'employment_status' => 'Tetap',
            'position' => 'Guru',
            'is_active' => true,
        ]);
    }
}
