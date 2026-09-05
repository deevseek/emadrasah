<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_new_user_can_have_multiple_roles_and_must_change_password(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $this->actingAs($admin)->post('/users', ['name'=>'Ahmad Fauzi','username'=>'ahmad.fauzi','email'=>'ahmad@example.test','roles'=>['guru','operator'],'password'=>'rahasia1','password_confirmation'=>'rahasia1','is_active'=>1])->assertRedirect();
        $user = User::where('username', 'ahmad.fauzi')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertEqualsCanonicalizing(['guru','operator'], $user->roles->pluck('name')->all());
    }

    public function test_operator_and_head_can_synchronize_multiple_user_roles(): void
    {
        $target = User::factory()->create(['must_change_password' => false]);
        $target->syncRoles(['guru']);
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->syncRoles(['operator']);

        $payload = ['name'=>$target->name,'username'=>$target->username,'email'=>$target->email,'roles'=>['guru','bendahara'],'is_active'=>1];
        $this->actingAs($operator)->put(route('users.update', $target), $payload)->assertRedirect();
        $this->assertTrue($target->fresh()->hasAllRoles(['guru','bendahara']));

        $head = User::factory()->create(['must_change_password' => false]);
        $head->syncRoles(['kepala-madrasah']);
        $payload['roles'] = ['guru','orang-tua'];
        $this->actingAs($head)->put(route('users.update', $target), $payload)->assertRedirect();
        $this->assertEqualsCanonicalizing(['guru','orang-tua'], $target->fresh()->roles->pluck('name')->all());
    }

    public function test_other_role_cannot_assign_roles_even_with_assignment_permission(): void
    {
        $actor = User::factory()->create(['must_change_password' => false]);
        $actor->syncRoles(['hrd']);
        Role::findByName('hrd')->givePermissionTo(['users.update','users.assign-role']);
        $target = User::factory()->create(['must_change_password' => false]);
        $target->syncRoles(['guru']);

        $this->actingAs($actor)->put(route('users.update', $target), ['name'=>$target->name,'username'=>$target->username,'email'=>$target->email,'roles'=>['guru','operator'],'is_active'=>1])
            ->assertSessionHasErrors('roles');
        $this->assertSame(['guru'], $target->fresh()->roles->pluck('name')->all());
    }

    public function test_email_address_can_be_used_as_username(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();

        $this->actingAs($admin)->post('/users', [
            'name' => 'Wildan Inda',
            'username' => 'WILDANINDA18@GMAIL.COM',
            'email' => 'wildaninda18@gmail.com',
            'roles' => ['guru'],
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'wildaninda18@gmail.com',
            'email' => 'wildaninda18@gmail.com',
        ]);
    }

    public function test_new_user_is_connected_to_active_personnel_with_the_same_email(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $personnel = Personnel::create([
            'full_name' => 'Khoiriyah, AH.',
            'email' => 'KHOIRLAILI15@gmail.com',
            'gender' => 'female',
            'employment_status' => 'Tetap',
            'position' => 'Guru BTAQ',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Khoiriyah, AH.',
            'username' => 'khoirlaili15@gmail.com',
            'email' => 'khoirlaili15@gmail.com',
            'roles' => ['guru'],
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
            'is_active' => 1,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $user = User::where('email', 'khoirlaili15@gmail.com')->firstOrFail();

        $this->assertSame($user->id, $personnel->refresh()->user_id);
        $this->assertSame($admin->id, $personnel->updated_by);
    }

    public function test_super_admin_can_delete_another_account_without_removing_its_history(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $target = User::factory()->create(['must_change_password' => false]);
        $target->syncRoles(['guru']);
        $personnel = Personnel::create([
            'user_id' => $target->id,
            'full_name' => 'Ahmad Fauzi',
            'gender' => 'male',
            'employment_status' => 'permanent',
            'position' => 'Guru',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $target->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('status', 'Akun pengguna berhasil dihapus.');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('model_has_roles', ['model_id' => $target->id]);
        $this->assertDatabaseHas('personnel', [
            'id' => $personnel->id,
            'user_id' => null,
            'updated_by' => $admin->id,
        ]);
        $this->assertNull(User::find($target->id));
    }

    public function test_deleted_account_releases_username_and_email_for_a_new_account(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $target = User::factory()->create([
            'username' => 'khoirlaili15@gmail.com',
            'email' => 'khoirlaili15@gmail.com',
            'must_change_password' => false,
        ]);
        $target->syncRoles(['guru']);
        DB::table('password_reset_tokens')->insert([
            'email' => $target->email,
            'token' => 'token-akun-lama',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->delete(route('users.destroy', $target))->assertRedirect();

        $deletedUser = User::withTrashed()->findOrFail($target->id);
        $this->assertStringStartsWith('deleted-user-'.$target->id.'-', $deletedUser->username);
        $this->assertStringEndsWith('@deleted.invalid', $deletedUser->email);
        $this->assertFalse($deletedUser->is_active);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'khoirlaili15@gmail.com']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Khoiriyah, AH.',
            'username' => 'khoirlaili15@gmail.com',
            'email' => 'khoirlaili15@gmail.com',
            'roles' => ['guru'],
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
            'is_active' => 1,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Khoiriyah, AH.',
            'username' => 'khoirlaili15@gmail.com',
            'email' => 'khoirlaili15@gmail.com',
            'deleted_at' => null,
        ]);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertNotSoftDeleted($admin);
    }

    public function test_personnel_list_treats_a_legacy_soft_deleted_account_as_disconnected(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $target = User::factory()->create(['must_change_password' => false]);
        $personnel = Personnel::create([
            'user_id' => $target->id,
            'full_name' => 'Hambali',
            'gender' => 'male',
            'employment_status' => 'permanent',
            'position' => 'Guru',
            'is_active' => true,
        ]);

        $target->delete();

        $listedPersonnel = Personnel::query()
            ->withExists('user as has_account')
            ->findOrFail($personnel->id);

        $this->assertFalse($listedPersonnel->hasAccount());
        $this->assertSame('Belum memiliki akun', $listedPersonnel->account_status_label);

        $this->actingAs($admin)
            ->get(route('personnel.index', ['search' => 'Hambali', 'account' => 'none']))
            ->assertOk()
            ->assertSee(route('personnel.show', $personnel), false)
            ->assertSee('Belum memiliki akun');

        $this->actingAs($admin)
            ->get(route('personnel.index', ['search' => 'Hambali', 'account' => 'connected']))
            ->assertOk()
            ->assertDontSee(route('personnel.show', $personnel), false);
    }

    public function test_user_without_delete_permission_cannot_delete_an_account(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->syncRoles(['operator']);
        $target = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($operator)
            ->delete(route('users.destroy', $target))
            ->assertForbidden();

        $this->assertNotSoftDeleted($target);
    }

    public function test_system_and_used_roles_cannot_be_deleted(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $this->actingAs($admin)->delete(route('roles.destroy', Role::where('name','guru')->firstOrFail()))->assertForbidden();
        $role = Role::create(['name'=>'wali-kelas','guard_name'=>'web','display_name'=>'Wali Kelas']);
        User::factory()->create()->syncRoles([$role]);
        $this->actingAs($admin)->delete(route('roles.destroy', $role))->assertStatus(422);
    }

    public function test_system_roles_are_available_in_display_order(): void
    {
        $roles = Role::query()
            ->inDisplayOrder()
            ->where('is_system', true)
            ->limit(count(config('roles.system_order')))
            ->pluck('name')
            ->all();

        $this->assertSame(config('roles.system_order'), $roles);
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
