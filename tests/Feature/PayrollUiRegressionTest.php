<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollUiRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@example.test')->firstOrFail();
    }

    public function test_super_admin_can_open_salary_profiles_and_periods_with_indonesian_headers(): void
    {
        $this->actingAs($this->admin)->get('/payroll-pegawai/salary-profiles')
            ->assertOk()
            ->assertSee('Profil Gaji')
            ->assertSee('Beranda / Keuangan / Payroll Pegawai / Profil Gaji')
            ->assertSee('Tambah Profil Gaji')
            ->assertSee('Pegawai Belum Memiliki Profil')
            ->assertDontSee('Dashboard')
            ->assertDontSee('Index')
            ->assertDontSee('Pegawai Belum Profil')
            ->assertDontSee('href="#"', false)
            ->assertDontSee('javascript:void', false);

        $this->actingAs($this->admin)->get('/payroll-pegawai/periods')
            ->assertOk()
            ->assertSee('Periode Payroll')
            ->assertSee('Beranda / Keuangan / Payroll Pegawai / Periode Payroll')
            ->assertSee('Tambah Periode Payroll')
            ->assertDontSee('Dashboard')
            ->assertDontSee('Index')
            ->assertDontSee('href="#"', false)
            ->assertDontSee('javascript:void', false);
    }

    public function test_read_only_payroll_user_cannot_see_or_open_create_actions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['salary-profiles.view', 'payroll-periods.view']);

        $this->actingAs($user)->get('/payroll-pegawai/salary-profiles')
            ->assertOk()
            ->assertDontSee('Tambah Profil Gaji');

        $this->actingAs($user)->get('/payroll-pegawai/periods')
            ->assertOk()
            ->assertDontSee('Tambah Periode Payroll');

        $this->actingAs($user)->get('/payroll-pegawai/salary-profiles/create')->assertForbidden();
        $this->actingAs($user)->get('/payroll-pegawai/periods/create')->assertForbidden();
    }

    public function test_payroll_sidebar_visibility_follows_permissions(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('Payroll Pegawai');

        $bendahara = User::factory()->create();
        $bendahara->givePermissionTo(['dashboard.view', 'payroll-periods.view']);
        $this->actingAs($bendahara)->get(route('dashboard'))->assertOk()->assertSee('Payroll Pegawai');

        $employee = User::factory()->create();
        $employee->givePermissionTo(['dashboard.view', 'payslips.view-own']);
        $this->actingAs($employee)->get(route('dashboard'))->assertOk()->assertSee('Slip Gaji Saya')->assertDontSee('Payroll Pegawai');

        $plain = User::factory()->create();
        $plain->givePermissionTo('dashboard.view');
        $this->actingAs($plain)->get(route('dashboard'))->assertOk()->assertDontSee('Payroll Pegawai')->assertDontSee('Slip Gaji Saya');
    }
}
