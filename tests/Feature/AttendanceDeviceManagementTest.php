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

    public function test_teacher_registers_own_device_as_pending(): void
    {
        $this->seed(AccessControlSeeder::class);
        $teacher = User::factory()->create(['email' => 'guru@example.test', 'must_change_password' => false]);
        $teacher->assignRole('guru');
        $personnel = $this->personnel(['email' => 'guru@example.test']);
        $otherPersonnel = $this->personnel(['full_name' => 'Guru Lain']);
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->actingAs($teacher)->get(route('hrd.attendance-devices.mine'))
            ->assertOk()->assertSee('Perangkat Absensi Saya');

        $this->actingAs($teacher)->post(route('hrd.attendance-devices.store'), [
            'personnel_id' => $otherPersonnel->id,
            'device_uuid' => $uuid,
            'device_name' => 'Ponsel Ustaz Ahmad',
            'platform' => 'Android',
            'browser' => 'Chrome',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('personnel_attendance_devices', [
            'personnel_id' => $personnel->id,
            'device_uuid_hash' => hash('sha256', $uuid),
            'is_trusted' => false,
            'trusted_by' => null,
            'revoked_at' => null,
        ]);
        $this->assertDatabaseMissing('personnel_attendance_devices', ['personnel_id' => $otherPersonnel->id]);
        $this->assertDatabaseMissing('personnel_attendance_devices', ['device_uuid_hash' => $uuid]);
    }

    public function test_operator_can_validate_and_revoke_pending_device(): void
    {
        $this->seed(AccessControlSeeder::class);
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->assignRole('operator');
        $device = $this->pendingDevice();

        $this->actingAs($operator)->get(route('hrd.attendance-devices.index'))
            ->assertOk()->assertSee('Validasi Perangkat Absensi');
        $this->actingAs($operator)->patch(route('hrd.attendance-devices.approve', $device))->assertRedirect();
        $this->assertTrue($device->refresh()->is_trusted);
        $this->assertSame($operator->id, $device->trusted_by);

        $this->actingAs($operator)->patch(route('hrd.attendance-devices.revoke', $device))->assertRedirect();
        $this->assertFalse($device->refresh()->is_trusted);
        $this->assertNotNull($device->revoked_at);
    }

    public function test_teacher_cannot_validate_devices_and_operator_cannot_register_for_a_teacher(): void
    {
        $this->seed(AccessControlSeeder::class);
        $teacher = User::factory()->create(['must_change_password' => false]);
        $teacher->assignRole('guru');
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->assignRole('operator');

        $this->actingAs($teacher)->get(route('hrd.attendance-devices.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('hrd.attendance-devices.mine'))->assertForbidden();
    }

    private function pendingDevice(): PersonnelAttendanceDevice
    {
        return PersonnelAttendanceDevice::create([
            'personnel_id' => $this->personnel()->id,
            'device_uuid_hash' => hash('sha256', 'pending-device'),
            'device_name' => 'Ponsel Baru',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'is_trusted' => false,
        ]);
    }

    private function personnel(array $attributes = []): Personnel
    {
        return Personnel::create(array_merge([
            'full_name' => 'Ustaz Ahmad',
            'gender' => 'male',
            'employment_status' => 'Tetap',
            'position' => 'Guru',
            'is_active' => true,
        ], $attributes));
    }
}
