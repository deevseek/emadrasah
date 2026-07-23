<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Employee;
use App\Models\LessonSchedule;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAcademicTestData;
use Tests\TestCase;

final class AcademicCrudTest extends TestCase
{
    use CreatesAcademicTestData;
    use RefreshDatabase;

    private User $admin;

    private array $records;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@example.test')->firstOrFail();

        [$year, $semester] = $this->createActiveAcademicPeriod('CRUD');
        $grade = $this->createGradeLevel('CRUD');
        $employee = $this->createTeacher(suffix: 'CRUD');
        $classroom = $this->createClassroom($year, $grade, $employee, 'CRUD');
        $subject = $this->createSubject('CRUD');
        $assignment = TeachingAssignment::query()->create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'employee_id' => $employee->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'weekly_hours' => 2,
            'is_active' => true,
        ]);
        $schedule = LessonSchedule::query()->create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'employee_id' => $employee->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'day_of_week' => DayOfWeek::Monday,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'room' => 'Ruang CRUD',
            'is_active' => true,
        ]);

        $this->records = compact('grade', 'classroom', 'subject', 'employee', 'assignment', 'schedule');
    }

    /**
     * @dataProvider academicCreateRoutes
     */
    public function test_academic_create_forms_render(string $routeName, string $storeRouteName): void
    {
        $response = $this->actingAs($this->admin)->get(route($routeName));

        $response->assertOk()
            ->assertSee('<form', false)
            ->assertSee('Simpan')
            ->assertSee(route($storeRouteName), false);
    }

    /**
     * @dataProvider academicEditRoutes
     */
    public function test_academic_edit_forms_render(string $routeName, string $updateRouteName, string $recordKey, string $existingValue): void
    {
        $record = $this->records[$recordKey];
        $response = $this->actingAs($this->admin)->get(route($routeName, $record));

        $response->assertOk()
            ->assertSee('<form', false)
            ->assertSee($existingValue)
            ->assertSee(route($updateRouteName, $record), false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('Batal');
    }


    public function test_homeroom_form_only_lists_class_teachers_and_rejects_principal(): void
    {
        $classroom = $this->records['classroom'];
        $classTeacher = Employee::query()->create([
            'name' => 'Guru Kelas Validasi',
            'gender' => Gender::Female,
            'employment_type' => EmploymentType::ClassTeacher,
            'employee_status' => EmployeeStatus::Permanent,
            'is_active' => true,
        ]);
        $principal = Employee::query()->create([
            'name' => 'Kepala Madrasah Validasi',
            'gender' => Gender::Female,
            'employment_type' => EmploymentType::Principal,
            'employee_status' => EmployeeStatus::Permanent,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('classrooms.homeroom.edit', $classroom))
            ->assertOk()
            ->assertSee($classTeacher->name)
            ->assertDontSee($principal->name);

        $this->put(route('classrooms.homeroom.update', $classroom), [
            'employee_id' => $principal->id,
            'started_at' => '2026-07-23',
        ])->assertSessionHasErrors('employee_id');
    }

    public static function academicCreateRoutes(): array
    {
        return [
            'grade levels create' => ['grade-levels.create', 'grade-levels.store'],
            'classrooms create' => ['classrooms.create', 'classrooms.store'],
            'subjects create' => ['subjects.create', 'subjects.store'],
            'employees create' => ['employees.create', 'employees.store'],
            'teaching assignments create' => ['teaching-assignments.create', 'teaching-assignments.store'],
            'schedules create' => ['schedules.create', 'schedules.store'],
        ];
    }

    public static function academicEditRoutes(): array
    {
        return [
            'grade levels edit' => ['grade-levels.edit', 'grade-levels.update', 'grade', 'Tingkat CRUD'],
            'classrooms edit' => ['classrooms.edit', 'classrooms.update', 'classroom', 'Kelas CRUD'],
            'subjects edit' => ['subjects.edit', 'subjects.update', 'subject', 'Mata Pelajaran Pengujian'],
            'employees edit' => ['employees.edit', 'employees.update', 'employee', 'Guru Pengujian'],
            'teaching assignments edit' => ['teaching-assignments.edit', 'teaching-assignments.update', 'assignment', 'Jam/Minggu'],
            'schedules edit' => ['schedules.edit', 'schedules.update', 'schedule', 'Ruang CRUD'],
        ];
    }
}
