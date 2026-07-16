<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@example.test')->firstOrFail();
    }

    public function test_super_admin_sees_new_module_navigation(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();

        $response->assertSee('Absensi Saya')
            ->assertSee('Absensi Siswa')
            ->assertSee('Jurnal Mengajar')
            ->assertSee('Dashboard BTAQ')
            ->assertSee('Dashboard Penilaian')
            ->assertSee('Dashboard Rapor');
    }

    public function test_user_without_permission_does_not_see_restricted_module_navigation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $response->assertDontSee('Absensi Saya')
            ->assertDontSee('Absensi Siswa')
            ->assertDontSee('Jurnal Mengajar')
            ->assertDontSee('Dashboard BTAQ')
            ->assertDontSee('Dashboard Penilaian')
            ->assertDontSee('Dashboard Rapor');
    }

    public function test_core_module_routes_render_without_view_or_component_errors(): void
    {
        $employeeUser = $this->createEmployeeUser();

        $employeeRoutes = [
            'employee-attendances.mine',
            'employee-leaves.index',
            'teaching-journals.index',
        ];

        foreach ($employeeRoutes as $routeName) {
            $response = $this->actingAs($employeeUser)->get(route($routeName));

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Route {$routeName} gagal dirender."
            );
        }

        $adminRoutes = [
            'student-attendances.index',
            'btaq.dashboard',
            'assessments.dashboard',
            'report-cards.dashboard',
        ];

        foreach ($adminRoutes as $routeName) {
            $response = $this->actingAs($this->admin)->get(route($routeName));

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Route {$routeName} gagal dirender."
            );
        }
    }

    private function createEmployeeUser(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Employee::query()->create([
            'user_id' => $user->id,
            'employee_number' => 'NAV-'.Str::upper(Str::random(8)),
            'name' => 'Guru Navigasi Pengujian',
            'gender' => Gender::Male,
            'employment_type' => EmploymentType::ClassTeacher,
            'employee_status' => EmployeeStatus::Permanent,
            'is_active' => true,
        ]);

        $user->givePermissionTo([
            'employee-attendances.view-own',
            'employee-leaves.view-own',
            'teaching-journals.view-own',
        ]);

        return $user->fresh('employee');
    }

    public function test_layout_renders_single_primary_sidebar(): void
    {
        $content = $this->actingAs($this->admin)->get(route('btaq.dashboard'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'class="app-sidebar"'));
    }
}
