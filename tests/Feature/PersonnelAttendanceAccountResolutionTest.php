<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Personnel, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelAttendanceAccountResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_self_attendance_when_personnel_email_matches(): void
    {
        $user = User::factory()->create(['email' => 'guru@example.test']);
        $personnel = $this->personnel(['email' => 'GURU@example.test']);

        $response = $this->withoutMiddleware()->actingAs($user)->get(route('hrd.attendance.mine'));

        $response->assertOk()->assertSee('Absensi Saya');
        $this->assertSame($user->id, $personnel->refresh()->user_id);
    }

    public function test_self_attendance_uses_same_origin_endpoints_and_friendly_connection_error(): void
    {
        config(['app.url' => 'https://alamat-konfigurasi-yang-salah.example']);
        $user = User::factory()->create(['email' => 'guru@example.test']);
        $this->personnel(['email' => 'guru@example.test']);

        $response = $this->withoutMiddleware()->actingAs($user)->get(route('hrd.attendance.mine'));

        $response->assertOk()
            ->assertSee('const challengeUrl="\/hrd\/attendance\/challenge"', false)
            ->assertSee('const faceUrl="\/hrd\/attendance\/face-verify"', false)
            ->assertSee('credentials:\'same-origin\'', false)
            ->assertSee('Tidak dapat terhubung ke server. Periksa koneksi internet Anda, lalu coba lagi.', false)
            ->assertDontSee('https:\/\/alamat-konfigurasi-yang-salah.example\/hrd\/attendance', false);
    }

    public function test_teacher_cannot_be_connected_to_inactive_personnel_for_self_attendance(): void
    {
        $user = User::factory()->create(['email' => 'guru@example.test']);
        $personnel = $this->personnel(['email' => 'guru@example.test', 'is_active' => false]);

        $response = $this->withoutMiddleware()->actingAs($user)->get(route('hrd.attendance.mine'));

        $response->assertForbidden();
        $this->assertNull($personnel->refresh()->user_id);
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
