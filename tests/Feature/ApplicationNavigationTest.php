<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $routes = [
            'employee-attendances.mine',
            'employee-leaves.index',
            'student-attendances.index',
            'teaching-journals.index',
            'btaq.dashboard',
            'assessments.dashboard',
            'report-cards.dashboard',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
    }

    public function test_layout_renders_single_primary_sidebar(): void
    {
        $content = $this->actingAs($this->admin)->get(route('btaq.dashboard'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'class="app-sidebar"'));
    }
}
