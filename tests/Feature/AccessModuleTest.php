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

    public function test_derived_permission_automatically_includes_view_permission(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $this->actingAs($admin)->post('/roles', ['display_name'=>'Petugas Akun','description'=>'Membuat akun','permissions'=>['users.create']])->assertRedirect();
        $this->assertTrue(Role::where('name','petugas-akun')->firstOrFail()->hasPermissionTo('users.view'));
    }
}
