<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_open_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertForbidden();
    }

    public function test_super_admin_can_open_foundation_dashboard(): void
    {
        $permission = Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Fondasi e-Madrasah')
            ->assertSee('Belum ada modul yang dipasang.')
            ->assertSee('Modul terpasang')
            ->assertDontSee('Siswa hadir');
    }
}
