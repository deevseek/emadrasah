<?php

declare(strict_types=1);

namespace Tests\Feature\Classrooms;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_classroom_index(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('classrooms.view');

        $this->actingAs($user)
            ->get(route('classrooms.index'))
            ->assertOk();
    }
}
