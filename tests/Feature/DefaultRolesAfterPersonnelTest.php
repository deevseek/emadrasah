<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use App\Services\Personnel\PersonnelRoleSuggestionService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DefaultRolesAfterPersonnelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_seeder_creates_idempotent_system_roles_without_touching_custom_roles_or_assignments(): void
    {
        $head=Role::where('name','kepala-madrasah')->firstOrFail();
        $this->assertSame('web',$head->guard_name);
        $this->assertSame('Kepala Madrasah',$head->display_name);
        $this->assertTrue($head->is_system);
        $custom=Role::create(['name'=>'wali-kelas','guard_name'=>'web','display_name'=>'Wali Kelas','is_system'=>false]);
        $user=User::factory()->create();$user->syncRoles([$custom]);
        $this->seed(AccessControlSeeder::class);
        $this->assertSame(1,Role::where('name','kepala-madrasah')->count());
        $this->assertDatabaseHas('roles',['name'=>'wali-kelas']);
        $this->assertTrue($user->fresh()->hasRole('wali-kelas'));
        $this->assertSame(1,ActivityLog::where('description','Membuat role sistem Kepala Madrasah.')->count());
    }

    public function test_default_permission_sets_follow_monitoring_and_operational_policy(): void
    {
        $head=Role::findByName('kepala-madrasah');
        foreach(['dashboard.view','school-profile.view','academic-periods.view','personnel.view','personnel.view-sensitive','personnel.export','users.view','roles.view'] as $permission)$this->assertTrue($head->hasPermissionTo($permission));
        foreach(['personnel.create','personnel.update','personnel.activate','personnel.manage-account','personnel.import','users.create','users.assign-role','roles.manage-permissions'] as $permission)$this->assertFalse($head->hasPermissionTo($permission));
        $operator=Role::findByName('operator');
        foreach(['personnel.view','personnel.create','personnel.update','personnel.activate','personnel.manage-account','personnel.view-sensitive','personnel.import','personnel.export'] as $permission)$this->assertTrue($operator->hasPermissionTo($permission));
        foreach(['rfid-card.view','rfid-card.issue','rfid-card.replace','rfid-card.disable','rfid-writer.use'] as $permission)$this->assertTrue($operator->hasPermissionTo($permission));
        $this->assertFalse($operator->hasPermissionTo('roles.manage-permissions'));$this->assertFalse($operator->hasPermissionTo('academic-periods.delete'));
        $teacher=Role::findByName('guru');$this->assertFalse($teacher->hasPermissionTo('personnel.view'));
        foreach(['dashboard.view','school-profile.view','academic-periods.view'] as $permission)$this->assertTrue($teacher->hasPermissionTo($permission));
    }

    public function test_super_admin_is_synchronized_while_other_roles_keep_manual_permissions(): void
    {
        $extra=Permission::findOrCreate('test.permission','web');
        $operator=Role::findByName('operator');$operator->givePermissionTo($extra);
        config()->set('permissions.test',['label'=>'Test','permissions'=>['test.permission'=>'Test']]);
        $this->seed(AccessControlSeeder::class);
        $this->assertTrue(Role::findByName('super-admin')->hasPermissionTo('test.permission'));
        $this->assertTrue($operator->fresh()->hasPermissionTo('test.permission'));
        $admin=User::where('username','administrator')->firstOrFail();
        $this->actingAs($admin)->get(route('roles.edit',Role::findByName('super-admin')))->assertForbidden();
    }

    public function test_seeder_creates_default_role_permissions_when_configuration_is_stale(): void
    {
        $permissions = config('permissions');
        unset($permissions['academic-subjects']);
        config()->set('permissions', $permissions);
        Permission::query()->where('name', 'like', 'academic-subjects.%')->delete();

        $this->seed(AccessControlSeeder::class);

        $this->assertTrue(Role::findByName('kepala-madrasah')->hasPermissionTo('academic-subjects.view'));
        $this->assertTrue(Role::findByName('operator')->hasPermissionTo('academic-subjects.manage'));
        $this->assertTrue(Role::findByName('guru')->hasPermissionTo('academic-subjects.view'));
    }

    public function test_role_pages_and_user_forms_use_configured_order_and_protect_system_roles(): void
    {
        $admin=User::where('username','administrator')->firstOrFail();
        $response=$this->actingAs($admin)->get(route('roles.index'))->assertOk()->assertSee('Kepala Madrasah');
        $names=$response->viewData('roles')->pluck('name')->take(4)->all();
        $this->assertSame(config('roles.system_order'),$names);
        $form=$this->get(route('users.create'))->assertOk();
        $this->assertSame(config('roles.system_order'),$form->viewData('roles')->pluck('name')->take(4)->all());
        $this->delete(route('roles.destroy',Role::findByName('kepala-madrasah')))->assertForbidden();
        $operator=User::factory()->create(['must_change_password'=>false]);$operator->syncRoles(['operator']);
        $this->actingAs($operator)->get(route('users.create'))->assertDontSee('value="super-admin"',false);
    }

    public function test_personnel_account_details_are_hidden_without_manage_permission(): void
    {
        $account=User::factory()->create(['name'=>'Uswatun Khasanah','username'=>'kepala.madrasah','email'=>'uswahasna82@gmail.com','must_change_password'=>false]);$account->syncRoles(['kepala-madrasah']);
        $personnel=$this->personnel(['user_id'=>$account->id]);
        $admin=User::where('username','administrator')->firstOrFail();
        $this->actingAs($admin)->get(route('personnel.show',$personnel))->assertOk()->assertSee('kepala.madrasah')->assertSee('Kepala Madrasah');
        $head=User::factory()->create(['must_change_password'=>false]);$head->syncRoles(['kepala-madrasah']);
        $this->actingAs($head)->get(route('personnel.show',$personnel))->assertOk()->assertSee('Akun terhubung')->assertDontSee('kepala.madrasah')->assertDontSee('uswahasna82@gmail.com');
    }

    public function test_personnel_face_enrollment_script_is_rendered_by_application_layout(): void
    {
        $admin = User::where('username', 'administrator')->firstOrFail();
        $personnel = $this->personnel();

        $this->actingAs($admin)
            ->get(route('personnel.show', $personnel))
            ->assertOk()
            ->assertSee('id="face-enrollment-open"', false)
            ->assertSee('navigator.mediaDevices.getUserMedia', false);
    }

    public function test_role_suggestion_is_explicit_authorized_and_logged(): void
    {
        $personnel=$this->personnel();$account=User::factory()->create();$account->syncRoles(['guru']);
        $suggestion=app(PersonnelRoleSuggestionService::class)->suggest($personnel);
        $this->assertSame('kepala-madrasah',$suggestion?->name);$this->assertTrue($account->hasRole('guru'));
        $admin=User::where('username','administrator')->firstOrFail();
        $this->actingAs($admin)->patch(route('personnel.account.update',$personnel),['user_id'=>$account->id])->assertRedirect();
        $this->assertTrue($account->fresh()->hasRole('guru'));
        $this->delete(route('personnel.account.destroy',$personnel))->assertRedirect();
        $this->patch(route('personnel.account.update',$personnel),['user_id'=>$account->id,'apply_suggested_role'=>1])->assertRedirect();
        $this->assertTrue($account->fresh()->hasRole('kepala-madrasah'));
        $this->assertDatabaseHas('activity_log',['description'=>"Mengubah role akun {$account->name} menjadi Kepala Madrasah."]);
        $head=User::factory()->create(['must_change_password'=>false]);$head->syncRoles(['kepala-madrasah']);
        $other=$this->personnel(['full_name'=>'Kepala Lain']);
        $this->actingAs($head)->patch(route('personnel.account.update',$other),['user_id'=>User::factory()->create()->id,'apply_suggested_role'=>1])->assertForbidden();
    }

    public function test_personnel_account_candidates_exclude_privileged_and_unsafe_accounts(): void
    {
        $admin=User::where('username','administrator')->firstOrFail();
        $otherAdmin=User::factory()->create(['name'=>'Admin Lain']);$otherAdmin->syncRoles(['super-admin']);
        $teacher=User::factory()->create(['name'=>'Nur Rohmah','email'=>'nur@example.test']);$teacher->syncRoles(['guru']);
        $linked=User::factory()->create(['name'=>'Sudah Terhubung']);$linked->syncRoles(['guru']);
        $this->personnel(['full_name'=>'Personalia Lain','user_id'=>$linked->id]);
        $personnel=$this->personnel(['full_name'=>'Nur Rohmah','email'=>'nur@example.test']);

        $response=$this->actingAs($admin)->get(route('personnel.account.edit',$personnel))->assertOk();

        $this->assertSame([$teacher->id],$response->viewData('accounts')->pluck('id')->all());
        $response->assertSee('Pilih akun yang belum terhubung')->assertDontSee('Admin Lain')->assertDontSee('Sudah Terhubung');
    }

    public function test_head_authorization_matches_read_only_defaults(): void
    {
        $head=User::factory()->create(['must_change_password'=>false]);$head->syncRoles(['kepala-madrasah']);$personnel=$this->personnel();
        $this->actingAs($head)->get(route('personnel.index'))->assertOk();
        $this->get(route('personnel.export'))->assertOk();
        $this->get(route('personnel.create'))->assertForbidden();$this->get(route('personnel.import.form'))->assertForbidden();$this->get(route('personnel.edit',$personnel))->assertForbidden();
        $this->get(route('users.index'))->assertOk();$this->get(route('users.create'))->assertForbidden();$this->get(route('roles.index'))->assertOk();$this->get(route('roles.edit',Role::findByName('guru')))->assertForbidden();
    }

    private function personnel(array $attributes=[]):Personnel{return Personnel::create(array_merge(['full_name'=>'Uswatun Khasanah','gender'=>'female','employment_status'=>'Tetap','position'=>'Kepala Madrasah','is_active'=>true],$attributes));}
}
