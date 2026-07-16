<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolSetting;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@example.test')->firstOrFail();
    }

    public function test_login_success_wrong_password_inactive_and_logout(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['email' => $admin->email, 'password' => 'bad'])->assertSessionHasErrors('email');

        $admin->update(['is_active' => false]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_password_reset_link_route(): void
    {
        $this->admin();
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);

        $this->post('/forgot-password', ['email' => 'admin@example.test'])->assertSessionHas('status');
    }

    public function test_active_user_without_permission_receives_forbidden(): void
    {
        $this->seed();
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_inactive_authenticated_user_is_logged_out_and_redirected(): void
    {
        $this->seed();
        $user = User::factory()->inactive()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $admin = $this->admin();

        foreach (Permission::pluck('name') as $permission) {
            $this->assertTrue($admin->can($permission));
        }
    }

    public function test_only_one_active_academic_year_and_semester(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $year = AcademicYear::create(['name' => '2027/2028', 'starts_on' => '2027-07-01', 'ends_on' => '2028-06-30']);

        app(AcademicPeriodService::class)->activateYear($year);

        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
        $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'term' => 1, 'starts_on' => '2027-07-01', 'ends_on' => '2027-12-31']);

        app(AcademicPeriodService::class)->activateSemester($semester);

        $this->assertSame(1, Semester::where('academic_year_id', $year->id)->where('is_active', true)->count());
    }

    public function test_school_profile_crud_and_invalid_upload_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/school-profile', ['school_name' => 'MI Baru', 'timezone' => 'Asia/Jakarta'])->assertSessionHas('status');
        $this->assertDatabaseHas('school_profiles', ['school_name' => 'MI Baru']);
        $this->actingAs($admin)->put('/school-profile', [
            'school_name' => 'MI Baru',
            'timezone' => 'Asia/Jakarta',
            'logo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('logo');
    }

    public function test_settings_access_user_management_last_super_admin_and_audit(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/settings')->assertOk();
        $setting = SchoolSetting::firstOrFail();
        $this->put(route('settings.update', $setting), ['value' => '123'])->assertSessionHas('status');

        $this->post('/users', [
            'name' => 'Operator',
            'email' => 'op@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => ['operator'],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'op@example.test']);
        $this->patch(route('users.toggle', $admin))->assertSessionHasErrors();
        $this->assertGreaterThan(0, Activity::count());
    }

    public function test_activity_log_can_store_event_value(): void
    {
        $admin = $this->admin();

        activity('foundation')
            ->causedBy($admin)
            ->event('foundation.test')
            ->withProperties(['source' => 'automated-test'])
            ->log('Pengujian penyimpanan event activity log.');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'foundation.test',
            'description' => 'Pengujian penyimpanan event activity log.',
        ]);
    }

    public function test_action_specific_user_permissions(): void
    {
        $this->seed();
        $role = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('users.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/users')->assertOk();
        $this->actingAs($user)->get('/users/create')->assertForbidden();
        $this->actingAs($user)->post('/users', [])->assertForbidden();
    }
}
