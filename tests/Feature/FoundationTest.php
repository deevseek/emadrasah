<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolProfile;
use App\Models\SchoolSetting;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
        $this->post('/login', ['email'=>$admin->email, 'password'=>'password'])->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['email'=>$admin->email, 'password'=>'bad'])->assertSessionHasErrors('email');
        $admin->update(['is_active'=>false]);
        $this->post('/login', ['email'=>$admin->email, 'password'=>'password'])->assertSessionHasErrors('email');
    }

    public function test_password_reset_link_route(): void
    {
        $this->admin();
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);
        $this->post('/forgot-password', ['email'=>'admin@example.test'])->assertSessionHas('status');
    }

    public function test_auth_and_permission_protection(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $user = User::factory()->create(['password'=>Hash::make('password')]);
        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $admin = $this->admin();
        foreach (Permission::pluck('name') as $permission) {
            $this->assertTrue($admin->hasPermission($permission));
        }
    }

    public function test_only_one_active_academic_year_and_semester(): void
    {
        $admin = $this->admin(); $this->actingAs($admin);
        $year = AcademicYear::create(['name'=>'2027/2028','starts_on'=>'2027-07-01','ends_on'=>'2028-06-30']);
        app(AcademicPeriodService::class)->activateYear($year);
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
        $semester = Semester::create(['academic_year_id'=>$year->id,'name'=>'Ganjil','term'=>1,'starts_on'=>'2027-07-01','ends_on'=>'2027-12-31']);
        app(AcademicPeriodService::class)->activateSemester($semester);
        $this->assertSame(1, Semester::where('academic_year_id',$year->id)->where('is_active', true)->count());
    }

    public function test_school_profile_crud_and_invalid_upload_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/school-profile', ['school_name'=>'MI Baru','timezone'=>'Asia/Jakarta'])->assertSessionHas('status');
        $this->assertDatabaseHas('school_profiles', ['school_name'=>'MI Baru']);
        $this->actingAs($admin)->put('/school-profile', ['school_name'=>'MI Baru','timezone'=>'Asia/Jakarta','logo'=>UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('logo');
    }

    public function test_settings_access_user_management_last_super_admin_and_audit(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/settings')->assertOk();
        $setting = SchoolSetting::first();
        $this->put(route('settings.update',$setting), ['value'=>'123'])->assertSessionHas('status');
        $role = Role::where('name','operator')->first();
        $this->post('/users', ['name'=>'Operator','email'=>'op@example.test','password'=>'password','roles'=>[$role->id]])->assertRedirect();
        $user = User::where('email','op@example.test')->first();
        $this->assertNotNull($user);
        $this->patch(route('users.toggle',$admin))->assertSessionHasErrors();
        $this->assertGreaterThan(0, ActivityLog::count());
    }
}
