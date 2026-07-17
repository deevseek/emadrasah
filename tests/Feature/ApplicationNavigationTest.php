<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdmissionType;
use App\Enums\DayOfWeek;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Enums\SubjectCategory;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

    public function test_super_admin_sees_module_four_and_five_navigation(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();

        $response->assertSee('Kelas &amp; Rombel', false)
            ->assertSee('Mata Pelajaran')
            ->assertSee('Penugasan Mengajar')
            ->assertSee('Jadwal Pelajaran')
            ->assertDontSee('Absensi Siswa')
            ->assertDontSee('Dashboard BTAQ')
            ->assertDontSee('Dashboard Penilaian')
            ->assertDontSee('Dashboard Rapor');
    }

    public function test_user_without_permission_does_not_see_module_four_and_five_navigation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $response->assertDontSee('Kelas &amp; Rombel', false)
            ->assertDontSee('Mata Pelajaran')
            ->assertDontSee('Penugasan Mengajar')
            ->assertDontSee('Jadwal Pelajaran');
    }

    public function test_module_four_and_five_menu_links_return_ok_for_permitted_user(): void
    {
        foreach (['classrooms.index', 'subjects.index', 'teaching-assignments.index', 'schedules.index'] as $routeName) {
            $response = $this->actingAs($this->admin)->get(route($routeName));

            $this->assertSame(200, $response->getStatusCode(), "Route {$routeName} gagal dirender.");
        }
    }

    public function test_dashboard_renders_real_database_metrics_and_actions(): void
    {
        $year = AcademicYear::query()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'term' => 1, 'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'is_active' => true]);
        $grade = GradeLevel::query()->create(['name' => 'Kelas 1', 'code' => '1', 'level' => 1, 'is_active' => true]);
        $employee = Employee::query()->create(['name' => 'Guru Penguji', 'gender' => Gender::Male, 'employment_type' => EmploymentType::SubjectTeacher, 'employee_status' => EmployeeStatus::Permanent, 'is_active' => true]);
        $classroom = Classroom::query()->create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'name' => '1A', 'code' => '1A', 'capacity' => 1, 'is_active' => true]);
        $subject = Subject::query()->create(['code' => 'MTK', 'name' => 'Matematika', 'category' => SubjectCategory::General, 'is_active' => true]);
        $assignment = TeachingAssignment::query()->create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'employee_id' => $employee->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'weekly_hours' => 2, 'is_active' => true]);
        $scheduledSubject = Subject::query()->create(['code' => 'IPA', 'name' => 'IPAS', 'category' => SubjectCategory::General, 'is_active' => true]);
        $unscheduledAssignment = TeachingAssignment::query()->create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'employee_id' => $employee->id, 'classroom_id' => $classroom->id, 'subject_id' => $scheduledSubject->id, 'weekly_hours' => 2, 'is_active' => true]);
        LessonSchedule::query()->create(['teaching_assignment_id' => $assignment->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id, 'classroom_id' => $classroom->id, 'subject_id' => $scheduledSubject->id, 'employee_id' => $employee->id, 'day_of_week' => DayOfWeek::Monday, 'starts_at' => '07:00', 'ends_at' => '08:00', 'is_active' => true]);
        $placed = Student::query()->create(['name' => 'Siswa Ditempatkan', 'gender' => Gender::Male, 'admission_type' => AdmissionType::NewStudent, 'student_status' => StudentStatus::Active, 'is_active' => true]);
        StudentEnrollment::query()->create(['student_id' => $placed->id, 'academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'enrollment_status' => EnrollmentStatus::Active]);
        Student::query()->create(['name' => 'Siswa Belum Ditempatkan', 'gender' => Gender::Female, 'admission_type' => AdmissionType::NewStudent, 'student_status' => StudentStatus::Active, 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();

        $response->assertSee('Beranda e-Madrasah')
            ->assertSee('2026/2027')
            ->assertSee('Siswa belum ditempatkan')
            ->assertSee('1 siswa belum ditempatkan')
            ->assertSee('1 kelas belum memiliki wali kelas')
            ->assertSee('1 mata pelajaran belum memiliki guru')
            ->assertSee('penugasan belum dijadwalkan')
            ->assertDontSee('dummy')
            ->assertDontSee('Dashboard dasar')
            ->assertDontSee('Pegawai tanpa akun');
    }

    public function test_app_layout_has_no_direct_database_queries_and_single_sidebar(): void
    {
        $layout = File::get(resource_path('views/components/app-layout.blade.php'));

        $this->assertStringNotContainsString('::query()', $layout);
        $this->assertStringNotContainsString('where(', $layout);

        $content = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($content, 'class="app-sidebar"'));
    }
}
