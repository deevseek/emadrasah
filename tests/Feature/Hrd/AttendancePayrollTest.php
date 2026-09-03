<?php

declare(strict_types=1);

namespace Tests\Feature\Hrd;

use App\Models\{ApplicationSetting, Personnel, PersonnelAttendance, User};
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendancePayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_hrd_processes_payroll_from_paid_attendance_and_saves_its_snapshot(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('personnel-payroll.create'));
        $personnel = $this->personnel();
        foreach (['hadir', 'terlambat', 'sakit', 'alpha'] as $day => $status) {
            PersonnelAttendance::create([
                'personnel_id' => $personnel->id,
                'attendance_date' => '2026-09-'.str_pad((string) ($day + 1), 2, '0', STR_PAD_LEFT),
                'shift_number' => 1,
                'status' => $status,
                'method' => 'manual',
            ]);
        }

        $response = $this->withoutMiddleware()->actingAs($user)->post(route('hrd.payroll.store'), [
            'personnel_id' => $personnel->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'pay_date' => '2026-09-30',
            'allowance' => 100000,
            'deduction' => 50000,
        ]);

        $payroll = $personnel->payrolls()->firstOrFail();
        $response->assertRedirect(route('hrd.payroll.show', $payroll));
        $this->assertSame('attendance', $payroll->calculation_method);
        $this->assertSame(3, $payroll->attendance_days);
        $this->assertSame(1, $payroll->attendance_summary['alpha']);
        $this->assertSame('300000.00', $payroll->base_salary);
        $this->assertSame('350000.00', $payroll->total);
    }

    public function test_payroll_form_only_lists_active_payroll_enabled_personnel(): void
    {
        $visible = $this->personnel(['full_name' => 'Pegawai Aktif']);
        $this->personnel(['full_name' => 'Payroll Dinonaktifkan', 'payroll_enabled' => false]);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('personnel-payroll.view'));

        $this->withoutMiddleware()->actingAs($user)->get(route('hrd.payroll.index'))
            ->assertOk()
            ->assertSee($visible->full_name)
            ->assertDontSee('Payroll Dinonaktifkan');
    }

    private function personnel(array $attributes = []): Personnel
    {
        ApplicationSetting::updateOrCreate(['key' => 'hrd_payroll_by_attendance_enabled'], ['value' => '1', 'type' => 'boolean', 'group' => 'hrd']);
        app(ApplicationSettingService::class)->clearCache();

        return Personnel::create(array_merge([
            'full_name' => 'Ustaz Ahmad',
            'gender' => 'male',
            'employment_status' => 'Tetap',
            'position' => 'Guru',
            'is_active' => true,
            'base_salary' => 3000000,
            'payroll_enabled' => true,
        ], $attributes));
    }
}
