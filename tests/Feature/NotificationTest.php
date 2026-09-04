<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ModuleActivityNotification;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_module_activity_notifies_authorized_users_except_actor(): void
    {
        $actor = User::factory()->create();
        $recipient = User::factory()->create();
        $recipient->givePermissionTo('students.view');
        $unauthorized = User::factory()->create();

        activity('students')->causedBy($actor)->log('Menambahkan siswa baru.');

        $this->assertSame(0, $actor->notifications()->count());
        $this->assertSame(1, $recipient->notifications()->count());
        $this->assertSame(0, $unauthorized->notifications()->count());
        $this->assertSame('Kesiswaan', $recipient->notifications()->first()->data['module_label']);
    }

    public function test_user_can_fetch_and_mark_own_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->notify(new ModuleActivityNotification(['module_label' => 'Akademik', 'message' => 'Nilai diperbarui.']));
        $other->notify(new ModuleActivityNotification(['module_label' => 'HRD', 'message' => 'Absensi diperbarui.']));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->getJson(route('notifications.latest'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.module', 'Akademik');

        $this->actingAs($user)->patchJson(route('notifications.read', $notification))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($user)->patchJson(route('notifications.read', $other->notifications()->firstOrFail()))
            ->assertNotFound();
    }
}
