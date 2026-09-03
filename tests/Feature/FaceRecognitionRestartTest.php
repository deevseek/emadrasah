<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FaceRecognitionRestartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_hrd_can_restart_python_service(): void
    {
        config()->set('face-recognition.driver', 'python');
        config()->set('face-recognition.restart_command', 'sudo systemctl restart emadrasah-face-recognition.service');
        config()->set('face-recognition.restart_timeout', 15);
        Process::fake();

        $this->actingAs($this->user(['hrd-settings.update']))
            ->postJson(route('application-settings.face-recognition.restart'))
            ->assertOk()
            ->assertJsonPath('message', 'Layanan Face Recognition sedang dimulai ulang.');

        Process::assertRan(fn (PendingProcess $process, $result): bool =>
            $process->command === 'sudo systemctl restart emadrasah-face-recognition.service'
            && $process->timeout === 15
        );
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'hrd',
            'description' => 'Memulai ulang layanan Face Recognition Python.',
        ]);
    }

    public function test_restart_requires_update_permission(): void
    {
        Process::fake();

        $this->actingAs($this->user(['hrd-settings.view']))
            ->postJson(route('application-settings.face-recognition.restart'))
            ->assertForbidden();

        Process::assertNothingRan();
    }

    public function test_restart_rejects_missing_command_without_running_a_process(): void
    {
        config()->set('face-recognition.driver', 'python');
        config()->set('face-recognition.restart_command', '');
        Process::fake();

        $this->actingAs($this->user(['hrd-settings.update']))
            ->postJson(route('application-settings.face-recognition.restart'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Perintah restart layanan Face Recognition belum dikonfigurasi.');

        Process::assertNothingRan();
    }

    public function test_failed_command_does_not_expose_process_output(): void
    {
        config()->set('face-recognition.driver', 'python');
        config()->set('face-recognition.restart_command', 'restart-face');
        Process::fake(['restart-face' => Process::result(output: 'rahasia', errorOutput: 'token internal', exitCode: 1)]);

        $response = $this->actingAs($this->user(['hrd-settings.update']))
            ->postJson(route('application-settings.face-recognition.restart'));

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Layanan Face Recognition gagal dimulai ulang. Periksa log layanan pada server.')
            ->assertDontSee('rahasia')
            ->assertDontSee('token internal');
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        return $user;
    }
}
