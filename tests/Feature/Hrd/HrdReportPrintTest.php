<?php

declare(strict_types=1);

namespace Tests\Feature\Hrd;

use App\Models\{Personnel, PersonnelAttendance, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrdReportPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_is_authorized_and_summarizes_attendance_by_personnel(): void
    {
        $this->get(route('hrd.reports.print'))->assertRedirect(route('login'));
        $plain = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($plain)->get(route('hrd.reports.print'))->assertForbidden();
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(Permission::findOrCreate('personnel-attendance.report'));

        foreach (range(1, 25) as $number) {
            $personnel = $this->personnel('Pegawai Laporan '.str_pad((string) $number, 2, '0', STR_PAD_LEFT));
            PersonnelAttendance::create(['personnel_id' => $personnel->id, 'attendance_date' => '2026-09-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'shift_number' => 1, 'status' => $number === 1 ? 'terlambat' : 'hadir', 'method' => 'manual', 'late_minutes' => $number === 1 ? 15 : 0]);
        }
        $outside = $this->personnel('Pegawai Di Luar Periode');
        PersonnelAttendance::create(['personnel_id' => $outside->id, 'attendance_date' => '2026-10-01', 'shift_number' => 1, 'status' => 'hadir', 'method' => 'manual']);

        $this->actingAs($user)->get(route('hrd.reports.print', ['start_date' => '2026-09-01', 'end_date' => '2026-09-30']))
            ->assertOk()->assertSee('LAPORAN HRD / KEPEGAWAIAN')->assertSee('REKAPITULASI KEHADIRAN PER PEGAWAI')->assertSee('Pegawai Laporan 01')->assertSee('Pegawai Laporan 25')->assertDontSee('Pegawai Di Luar Periode')->assertSee('1 kali')->assertSee('15 menit')->assertDontSee('RINCIAN KEHADIRAN PEGAWAI')->assertDontSee('x-layouts.app')->assertDontSee('pagination');
    }

    public function test_index_print_link_keeps_period_and_invalid_period_is_rejected(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(Permission::findOrCreate('personnel-attendance.report'));
        $query = ['start_date' => '2026-09-01', 'end_date' => '2026-09-30'];
        $this->actingAs($user)->get(route('hrd.reports.index', $query))->assertOk()->assertSee(route('hrd.reports.print', $query));
        $this->actingAs($user)->get(route('hrd.reports.print', ['start_date' => '2026-09-30', 'end_date' => '2026-09-01']))->assertSessionHasErrors('end_date');
    }

    public function test_index_pagination_exposes_attendance_from_later_dates_in_the_selected_period(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(Permission::findOrCreate('personnel-attendance.report'));

        foreach (range(1, 21) as $number) {
            $personnel = $this->personnel('Pegawai Periode '.str_pad((string) $number, 2, '0', STR_PAD_LEFT));
            PersonnelAttendance::create([
                'personnel_id' => $personnel->id,
                'attendance_date' => $number === 21 ? '2026-09-21' : '2026-09-08',
                'shift_number' => 1,
                'status' => 'hadir',
                'method' => 'manual',
            ]);
        }

        $query = ['start_date' => '2026-09-08', 'end_date' => '2026-09-21'];
        $this->actingAs($user)->get(route('hrd.reports.index', $query))
            ->assertOk()
            ->assertSee('Menampilkan 1–20 dari 21 data absensi.')
            ->assertSee('page=2')
            ->assertSee('start_date=2026-09-08');
        $this->actingAs($user)->get(route('hrd.reports.index', [...$query, 'page' => 2]))
            ->assertOk()
            ->assertSee('Pegawai Periode 21')
            ->assertSee('21/09/2026');
    }

    private function personnel(string $name): Personnel
    {
        return Personnel::create(['full_name' => $name, 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'is_active' => true]);
    }
}
