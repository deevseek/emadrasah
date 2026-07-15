<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_permission_through_role(): void
    {
        Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'tester', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');
        $user = User::factory()->create();

        $user->assignRole('tester');

        $this->assertTrue($user->can('dashboard.view'));
    }
}
