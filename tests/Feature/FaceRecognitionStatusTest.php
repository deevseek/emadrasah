<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\FaceRecognitionService;
use App\Models\Personnel;
use App\Models\PersonnelFaceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FaceRecognitionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_hrd_can_see_healthy_service_without_exposing_token(): void
    {
        config()->set('face-recognition.token', 'rahasia-yang-tidak-boleh-tampil');
        config()->set('face-recognition.url', 'http://internal-user:internal-password@127.0.0.1:8791');
        $this->mockHealth(['status' => 'ok', 'engine' => 'sface', 'model_loaded' => true, 'detail' => null]);

        $response = $this->actingAs($this->user(['application-settings.view', 'hrd-settings.view']))
            ->get(route('application-settings.edit'));

        $response->assertOk()
            ->assertSee('Status Face Recognition')
            ->assertSee('SFace')
            ->assertSee('127.0.0.1:8791')
            ->assertSee('Aktif')
            ->assertDontSee('rahasia-yang-tidak-boleh-tampil')
            ->assertDontSee('internal-password');
    }

    public function test_unavailable_service_does_not_break_settings_page(): void
    {
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('provider')->andReturn('python');
            $mock->shouldReceive('health')->andThrow(new RuntimeException('connection refused with secret'));
        });

        $this->actingAs($this->user(['application-settings.view', 'hrd-settings.view']))
            ->get(route('application-settings.edit'))
            ->assertOk()
            ->assertSee('Tidak Tersedia')
            ->assertSee('Terputus')
            ->assertDontSee('connection refused with secret');
    }

    public function test_loaded_false_is_reported_as_unavailable(): void
    {
        $this->mockHealth(['status' => 'ok', 'engine' => 'sface', 'model_loaded' => false, 'detail' => 'Model SFace gagal dimuat.']);

        $this->actingAs($this->user(['hrd-settings.view']))
            ->postJson(route('application-settings.face-recognition.status'))
            ->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonPath('connected', true)
            ->assertJsonPath('model_loaded', false)
            ->assertJsonPath('detail', 'Model SFace gagal dimuat.');
    }

    public function test_enrollment_statistics_only_include_active_personnel_and_profiles(): void
    {
        $this->mockHealth(['status' => 'ok', 'engine' => 'sface', 'model_loaded' => true]);
        $registered = $this->personnel(true);
        $this->personnel(true);
        $inactivePersonnel = $this->personnel(false);
        $activeProfile = $this->profile($registered, 'active');
        $this->profile($inactivePersonnel, 'active');
        $inactiveProfile = $this->profile($this->personnel(true), 'inactive');
        $activeProfile->samples()->createMany([$this->sample('front'), $this->sample('left')]);
        $inactiveProfile->samples()->create($this->sample('front'));

        $this->actingAs($this->user(['hrd-settings.view']))
            ->postJson(route('application-settings.face-recognition.status'))
            ->assertOk()
            ->assertJsonPath('statistics.registered', 1)
            ->assertJsonPath('statistics.samples', 2)
            ->assertJsonPath('statistics.unregistered', 2);
    }

    public function test_status_endpoint_requires_hrd_settings_view_permission(): void
    {
        $this->mockHealth(['status' => 'ok', 'model_loaded' => true]);

        $this->actingAs($this->user([]))->postJson(route('application-settings.face-recognition.status'))->assertForbidden();

        $admin = $this->user([]);
        $admin->assignRole(Role::findOrCreate('super-admin'));
        $this->actingAs($admin)->postJson(route('application-settings.face-recognition.status'))->assertOk();
    }

    private function mockHealth(array $health): void
    {
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) use ($health): void {
            $mock->shouldReceive('provider')->andReturn('python');
            $mock->shouldReceive('health')->andReturn($health);
        });
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        return $user;
    }

    private function personnel(bool $active): Personnel
    {
        return Personnel::create([
            'full_name' => fake()->unique()->name(),
            'gender' => 'male',
            'employment_status' => 'permanent',
            'position' => 'Staf',
            'is_active' => $active,
        ]);
    }

    private function profile(Personnel $personnel, string $status): PersonnelFaceProfile
    {
        return PersonnelFaceProfile::create([
            'personnel_id' => $personnel->id,
            'provider' => 'python',
            'model_name' => 'sface',
            'model_version' => '1',
            'status' => $status,
            'primary_reference_photo_path' => 'private/reference.jpg',
            'registered_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function sample(string $pose): array
    {
        return [
            'photo_path' => 'private/'.$pose.'.jpg',
            'pose' => $pose,
            'quality_score' => 0.95,
            'embedding' => [0.1, 0.2],
            'embedding_version' => '1',
        ];
    }
}
