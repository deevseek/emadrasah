<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\SubjectCategory;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleFiveAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $teacherUser;

    private User $plainUser;

    private Employee $teacher;

    private Employee $otherTeacher;

    private TeachingAssignment $ownAssignment;

    private TeachingAssignment $otherAssignment;

    private LessonSchedule $ownSchedule;

    private LessonSchedule $otherSchedule;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->superAdmin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->teacherUser = User::factory()->create(['name' => 'Guru Akses Sendiri']);
        $this->teacherUser->assignRole('guru-mata-pelajaran');
        $this->plainUser = User::factory()->create(['name' => 'Tanpa Izin']);

        $year = AcademicYear::where('is_active', true)->firstOrFail();
        $semester = Semester::where('academic_year_id', $year->id)->firstOrFail();
        $grade = GradeLevel::firstOrFail();
        $classroom = Classroom::firstOrFail();
        $otherClassroom = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'name' => 'Kelas Uji Lain', 'code' => 'KUL', 'capacity' => 30, 'is_active' => true]);
        $subject = Subject::firstOrFail();
        $otherSubject = Subject::create(['code' => 'UJI-LAIN', 'name' => 'Mapel Uji Lain', 'category' => SubjectCategory::General, 'is_active' => true]);
        $this->teacher = Employee::create(['user_id' => $this->teacherUser->id, 'name' => 'Guru Sendiri', 'gender' => Gender::Male, 'employment_type' => EmploymentType::SubjectTeacher, 'employee_status' => EmployeeStatus::Permanent, 'is_active' => true]);
        $this->otherTeacher = Employee::create(['name' => 'Guru Orang Lain', 'gender' => Gender::Female, 'employment_type' => EmploymentType::SubjectTeacher, 'employee_status' => EmployeeStatus::Permanent, 'is_active' => true]);
        $this->ownAssignment = TeachingAssignment::create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'employee_id' => $this->teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'weekly_hours' => 2, 'is_active' => true]);
        $this->otherAssignment = TeachingAssignment::create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'employee_id' => $this->otherTeacher->id, 'classroom_id' => $otherClassroom->id, 'subject_id' => $otherSubject->id, 'weekly_hours' => 2, 'is_active' => true]);
        $this->ownSchedule = $this->schedule($this->ownAssignment, DayOfWeek::Monday, '07:00', '08:00');
        $this->otherSchedule = $this->schedule($this->otherAssignment, DayOfWeek::Tuesday, '09:00', '10:00');
    }

    public function test_super_admin_can_open_module_five_pages(): void
    {
        $this->actingAs($this->superAdmin)->get(route('subjects.index'))->assertOk();
        $this->get(route('teaching-assignments.index'))->assertOk();
        $this->get(route('schedules.index'))->assertOk();
        $this->get(route('teaching-assignments.create'))->assertOk();
        $this->get(route('schedules.create'))->assertOk();
        $this->get(route('teaching-assignments.show', $this->ownAssignment))->assertOk();
        $this->get(route('schedules.show', $this->ownSchedule))->assertOk();
    }

    public function test_teacher_view_own_is_scoped_and_cannot_mutate(): void
    {
        $this->actingAs($this->teacherUser)->get(route('teaching-assignments.index'))
            ->assertOk()
            ->assertSee('Guru Sendiri')
            ->assertDontSee('Guru Orang Lain');

        $this->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Guru Sendiri')
            ->assertDontSee('Guru Orang Lain');

        $this->get(route('teaching-assignments.show', $this->ownAssignment))->assertOk();
        $this->get(route('teaching-assignments.show', $this->otherAssignment))->assertForbidden();
        $this->get(route('schedules.show', $this->ownSchedule))->assertOk();
        $this->get(route('schedules.show', $this->otherSchedule))->assertForbidden();
        $this->get(route('teaching-assignments.create'))->assertForbidden();
        $this->get(route('schedules.create'))->assertForbidden();
    }

    public function test_user_without_module_five_permission_is_forbidden_and_navigation_hidden(): void
    {
        $this->plainUser->givePermissionTo('dashboard.view');

        $this->actingAs($this->plainUser)->get(route('teaching-assignments.index'))->assertForbidden();
        $this->get(route('schedules.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk()
            ->assertDontSee('Penugasan Mengajar')
            ->assertDontSee('Jadwal Pelajaran');
    }

    public function test_navigation_matches_view_and_view_own_permissions(): void
    {
        $this->actingAs($this->superAdmin)->get(route('dashboard'))->assertOk()
            ->assertSee('Penugasan Mengajar')
            ->assertSee('Jadwal Pelajaran');

        $this->actingAs($this->teacherUser)->get(route('dashboard'))->assertOk()
            ->assertSee('Penugasan Mengajar')
            ->assertSee('Jadwal Pelajaran');
    }

    public function test_role_permission_seeder_is_idempotent_and_super_admin_receives_module_five_permissions(): void
    {
        $before = Permission::count();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame($before, Permission::count());
        foreach (['subjects.view', 'subjects.create', 'subjects.update', 'subjects.activate', 'subjects.export', 'teaching-assignments.view', 'teaching-assignments.view-own', 'teaching-assignments.create', 'teaching-assignments.update', 'teaching-assignments.change-teacher', 'teaching-assignments.activate', 'teaching-assignments.export', 'schedules.view', 'schedules.view-own', 'schedules.create', 'schedules.update', 'schedules.activate', 'schedules.export', 'schedules.print'] as $permission) {
            $this->assertTrue($this->superAdmin->fresh()->can($permission));
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
        }
    }

    private function schedule(TeachingAssignment $assignment, DayOfWeek $day, string $startsAt, string $endsAt): LessonSchedule
    {
        return LessonSchedule::create([
            'teaching_assignment_id' => $assignment->id,
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $assignment->semester_id,
            'classroom_id' => $assignment->classroom_id,
            'subject_id' => $assignment->subject_id,
            'employee_id' => $assignment->employee_id,
            'day_of_week' => $day,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'lesson_hours' => 1,
            'room' => 'Ruang Uji',
            'is_active' => true,
        ]);
    }
}
