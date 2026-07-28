<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\HomeroomAssignment;
use App\Models\TeachingAssignment;
use Database\Seeders\AcademicPeriodSeeder;
use Database\Seeders\MimnuAcademicMasterSeeder;
use Database\Seeders\MimnuSubjectSeeder;
use Database\Seeders\MimnuTeachingAssignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MimnuTeachingAssignmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_staff_classrooms_and_assignments_are_seeded_idempotently(): void
    {
        $this->seed(AcademicPeriodSeeder::class);
        $this->seed(MimnuAcademicMasterSeeder::class);
        $this->seed(MimnuSubjectSeeder::class);
        $this->seed(MimnuTeachingAssignmentSeeder::class);
        $assignmentCount = TeachingAssignment::query()->count();

        $this->seed(MimnuAcademicMasterSeeder::class);
        $this->seed(MimnuTeachingAssignmentSeeder::class);

        $this->assertSame(25, Employee::query()->whereNotNull('employee_number')->count());
        $this->assertSame(12, Classroom::query()->count());
        $this->assertSame(12, HomeroomAssignment::query()->count());
        $this->assertSame($assignmentCount, TeachingAssignment::query()->count());

        $principal = Employee::query()->where('employee_number', '620.0720.001')->firstOrFail();
        $this->assertSame(EmployeeStatus::FoundationPermanentTeacher, $principal->employee_status);
        $this->assertSame('20367380193001', $principal->peg_id);
        $this->assertSame('S2', $principal->last_education);

        $this->assertDatabaseHas('employees', [
            'employee_number' => '620.0722.046',
            'name' => 'SURYADI',
            'employee_status' => EmployeeStatus::FoundationPermanentEmployee->value,
            'position' => 'Petugas Kebersihan',
        ]);
    }

    public function test_workloads_follow_the_official_teacher_sheets(): void
    {
        $this->seed(AcademicPeriodSeeder::class);
        $this->seed(MimnuAcademicMasterSeeder::class);
        $this->seed(MimnuSubjectSeeder::class);
        $this->seed(MimnuTeachingAssignmentSeeder::class);

        $this->assertSame(32, Employee::query()->where('employee_number', '620.0725.040')->value('weekly_teaching_hours'));
        $this->assertSame(42, Employee::query()->where('employee_number', '620.0726.047')->value('weekly_teaching_hours'));
        $this->assertSame(40, Employee::query()->where('employee_number', '620.0124.029')->value('weekly_teaching_hours'));
        $this->assertSame(12, Employee::query()->where('employee_number', '620.0824.038')->value('weekly_teaching_hours'));
        $this->assertSame(24, Employee::query()->where('employee_number', '620.0720.001')->value('weekly_teaching_hours'));
        $this->assertNull(Employee::query()->where('employee_number', '620.0722.046')->value('weekly_teaching_hours'));

        $this->assertAssignment('620.0725.040', 'I-AR-RAHMAN', 'MTK', 4);
        $this->assertAssignment('620.0726.047', 'I-AS-SALAM', 'NUM', 2);
        $this->assertAssignment('620.0124.029', 'III-AL-KHALIQ', 'IPAS', 5);
        $this->assertAssignment('620.0724.037', 'VI-AL-MAJID', 'TKA', 2);
        $this->assertAssignment('620.0824.038', 'I-AR-RAHMAN', 'BTAQ', 10);
        $this->assertAssignment('620.0726.048', 'I-AS-SALAM', 'TAQ', 2);
        $this->assertAssignment('620.0923.027', 'VI-AL-MAJID', 'BAR', 2);
    }

    private function assertAssignment(string $employeeCode, string $classroomCode, string $subjectCode, int $hours): void
    {
        $assignment = TeachingAssignment::query()
            ->whereRelation('employee', 'employee_number', $employeeCode)
            ->whereRelation('classroom', 'code', $classroomCode)
            ->whereRelation('subject', 'code', $subjectCode)
            ->firstOrFail();

        $this->assertSame($hours, $assignment->weekly_hours);
    }
}
