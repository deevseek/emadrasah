<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeder_without_password_still_seeds_roles_without_creating_admin(): void
    {
        User::query()->delete();
        config(['app.env' => 'production']);

        $this->seed(AccessControlSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_login_accepts_email_username_and_username_is_case_insensitive(): void
    {
        $user = User::factory()->create(['username' => 'ahmad.fauzi', 'password' => Hash::make('rahasia'), 'must_change_password' => false]);
        $this->post('/login', ['login' => $user->email, 'password' => 'rahasia'])->assertRedirect('/dashboard');
        $this->post('/logout');
        $this->post('/login', ['login' => 'AHMAD.FAUZI', 'password' => 'rahasia'])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('login_histories', ['login_identifier' => 'ahmad.fauzi', 'successful' => true]);
    }

    public function test_new_user_has_exactly_one_role_and_must_change_password(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $this->actingAs($admin)->post('/users', ['name'=>'Ahmad Fauzi','username'=>'ahmad.fauzi','email'=>'ahmad@example.test','role'=>'guru','password'=>'rahasia1','password_confirmation'=>'rahasia1','is_active'=>1])->assertRedirect();
        $user = User::where('username', 'ahmad.fauzi')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertSame(['guru'], $user->roles->pluck('name')->all());
    }

    public function test_system_and_used_roles_cannot_be_deleted(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $this->actingAs($admin)->delete(route('roles.destroy', Role::where('name','guru')->firstOrFail()))->assertForbidden();
        $role = Role::create(['name'=>'wali-kelas','guard_name'=>'web','display_name'=>'Wali Kelas']);
        User::factory()->create()->syncRoles([$role]);
        $this->actingAs($admin)->delete(route('roles.destroy', $role))->assertStatus(422);
    }

    public function test_four_system_roles_are_available_in_display_order(): void
    {
        $roles = Role::query()->inDisplayOrder()->where('is_system', true)->pluck('name')->all();

        $this->assertSame(['super-admin', 'kepala-madrasah', 'operator', 'guru'], $roles);
    }

    public function test_default_role_permission_matrix_is_safe(): void
    {
        $all = collect(config('permissions'))->flatMap(fn (array $module) => array_keys($module['permissions']));
        $superAdmin = Role::where('name', 'super-admin')->firstOrFail();
        $kepala = Role::where('name', 'kepala-madrasah')->firstOrFail();
        $operator = Role::where('name', 'operator')->firstOrFail();
        $guru = Role::where('name', 'guru')->firstOrFail();

        $this->assertEqualsCanonicalizing($all->all(), $superAdmin->permissions->pluck('name')->all());
        $this->assertTrue($kepala->hasAllPermissions(['academic-reports.export', 'teaching-journals.view-all']));
        $this->assertFalse($kepala->hasPermissionTo('academic-attendance.manage'));
        $this->assertTrue($operator->hasAllPermissions(['academic-attendance.manage', 'users.assign-role', 'roles.view']));
        $this->assertTrue($operator->hasAllPermissions([
            'rfid-card.view',
            'rfid-card.issue',
            'rfid-card.replace',
            'rfid-card.disable',
            'rfid-writer.use',
        ]));
        $this->assertFalse($operator->hasPermissionTo('roles.manage-permissions'));
        $this->assertTrue($guru->hasAllPermissions(['academic-attendance.manage', 'academic-grades.manage']));
        $this->assertFalse($guru->hasAnyPermission(['teaching-journals.view-all']));
        $this->assertFalse($guru->hasAnyPermission([
            'rfid-card.view',
            'rfid-card.issue',
            'rfid-card.replace',
            'rfid-card.disable',
            'rfid-writer.use',
        ]));
    }

    public function test_reseeding_preserves_adjusted_and_custom_roles(): void
    {
        $operator = Role::where('name', 'operator')->firstOrFail();
        $operator->revokePermissionTo('academic-attendance.manage');
        $custom = Role::create(['name'=>'auditor', 'guard_name'=>'web', 'display_name'=>'Auditor', 'is_system'=>false]);
        $custom->givePermissionTo('dashboard.view');

        $this->seed(AccessControlSeeder::class);

        $this->assertFalse($operator->refresh()->hasPermissionTo('academic-attendance.manage'));
        $this->assertTrue($custom->refresh()->hasPermissionTo('dashboard.view'));
    }

    public function test_derived_permission_automatically_includes_view_permission(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $this->actingAs($admin)->post('/roles', ['display_name'=>'Petugas Akun','description'=>'Membuat akun','permissions'=>['users.create']])->assertRedirect();
        $this->assertTrue(Role::where('name','petugas-akun')->firstOrFail()->hasPermissionTo('users.view'));
    }
}
