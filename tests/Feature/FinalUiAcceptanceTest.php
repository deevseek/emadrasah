<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinalUiAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->activeClassroom();
    }

    public function test_priority_pages_render_real_indonesian_ui_for_super_admin(): void
    {
        $pages = [
            '/academic/subjects' => 'Mata Pelajaran',
            '/teaching-journals' => 'Jurnal Mengajar',
            '/student-attendances/create' => 'Input Absensi Siswa',
            '/penilaian-rapor/konfigurasi' => 'Konfigurasi Penilaian',
            '/student-finance/fee-types' => 'Jenis Tagihan',
            '/payroll-pegawai/components' => 'Komponen Payroll',
            '/payroll-pegawai/salary-profiles' => 'Profil Gaji',
            '/payroll-pegawai/periods' => 'Periode Payroll',
            '/payroll-pegawai/reports' => 'Laporan Payroll',
        ];

        foreach ($pages as $uri => $title) {
            $this->actingAs($this->admin)->get($uri)
                ->assertOk()
                ->assertSee($title)
                ->assertSee('Beranda')
                ->assertDontSee('Subjects / Index')
                ->assertDontSee('Teaching Journals')
                ->assertDontSee('Student Finance')
                ->assertDontSee('Fee Types')
                ->assertDontSee('Payroll Reports')
                ->assertDontSee('href="#"', false)
                ->assertDontSee('javascript:void', false);
        }
    }

    public function test_sidebar_finance_visibility_and_actions_follow_permissions(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Keuangan Siswa')
            ->assertSee('Payroll Pegawai');

        $bendahara = User::factory()->create();
        $bendahara->givePermissionTo(['dashboard.view', 'student-fee-types.view', 'payroll-components.view', 'salary-profiles.view', 'payroll-periods.view', 'payroll-reports.view']);

        $this->actingAs($bendahara)->get('/dashboard')
            ->assertOk()
            ->assertSee('Keuangan Siswa')
            ->assertSee('Payroll Pegawai');

        $this->actingAs($bendahara)->get('/student-finance/fee-types')->assertOk()->assertDontSee('Tambah Jenis Tagihan');
        $this->actingAs($bendahara)->get('/payroll-pegawai/components')->assertOk()->assertDontSee('Tambah Komponen');

        $guru = User::factory()->create();
        $guru->givePermissionTo(['dashboard.view', 'subjects.view', 'teaching-journals.view-own']);

        $this->actingAs($guru)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Keuangan Siswa')
            ->assertDontSee('Payroll Pegawai');
    }

    public function test_student_attendance_create_authorization_for_admin_teacher_and_plain_user(): void
    {
        $classroom = $this->activeClassroom();
        $teacherUser = User::factory()->create();
        $teacher = Employee::query()->create([
            'user_id' => $teacherUser->id,
            'name' => 'Guru Kelas UAT',
            'gender' => Gender::Female,
            'employment_type' => EmploymentType::ClassTeacher,
            'employee_status' => EmployeeStatus::Permanent,
            'is_active' => true,
        ]);
        $classroom->forceFill(['homeroom_teacher_id' => $teacher->id])->save();
        $teacherUser->assignRole('guru-kelas');

        $this->actingAs($this->admin)->get('/student-attendances/create')->assertOk();
        $this->actingAs($teacherUser)->get('/student-attendances/create?classroom_id='.$classroom->id)->assertOk();

        $plain = User::factory()->create();
        $plain->givePermissionTo('dashboard.view');
        $this->actingAs($plain)->get('/student-attendances/create')->assertForbidden();
    }

    private function activeClassroom(): Classroom
    {
        $year = AcademicYear::query()->firstOrCreate(['name' => '2026/2027'], ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $year->forceFill(['is_active' => true])->save();
        Semester::query()->firstOrCreate(['academic_year_id' => $year->id, 'name' => 'Ganjil'], ['term' => 1, 'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'is_active' => true]);
        $grade = GradeLevel::query()->firstOrCreate(['code' => '1'], ['name' => 'Kelas 1', 'level' => 1, 'is_active' => true]);

        return Classroom::query()->firstOrCreate(['code' => '1A'], ['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'name' => '1A', 'capacity' => 32, 'is_active' => true]);
    }
}
