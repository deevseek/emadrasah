<?php

declare(strict_types=1);

namespace Tests\Feature;

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

        $this->assertSame(24, Employee::query()->whereBetween('employee_number', [1, 24])->count());
        $this->assertSame(12, Classroom::query()->count());
        $this->assertSame(12, HomeroomAssignment::query()->count());
        $this->assertSame($assignmentCount, TeachingAssignment::query()->count());
    }

    public function test_workloads_follow_the_official_teacher_sheets(): void
    {
        $this->seed(AcademicPeriodSeeder::class);
        $this->seed(MimnuAcademicMasterSeeder::class);
        $this->seed(MimnuSubjectSeeder::class);
        $this->seed(MimnuTeachingAssignmentSeeder::class);

        $this->assertSame(40, Employee::query()->where('employee_number', '2')->value('weekly_teaching_hours'));
        $this->assertSame(44, Employee::query()->where('employee_number', '4')->value('weekly_teaching_hours'));
        $this->assertSame(47, Employee::query()->where('employee_number', '7')->value('weekly_teaching_hours'));
        $this->assertSame(10, Employee::query()->where('employee_number', '14')->value('weekly_teaching_hours'));
        $this->assertSame(24, Employee::query()->where('employee_number', '1')->value('weekly_teaching_hours'));

        $this->assertAssignment('2', 'I-AR-RAHMAN', 'MTK', 4);
        $this->assertAssignment('4', 'I-AS-SALAM', 'NUM', 2);
        $this->assertAssignment('7', 'III-AL-KHALIQ', 'IPAS', 5);
        $this->assertAssignment('13', 'VI-AL-MAJID', 'TKA', 2);
        $this->assertAssignment('14', 'I-AR-RAHMAN', 'BTAQ', 10);
        $this->assertAssignment('21', 'I-AS-SALAM', 'TAQ', 2);
        $this->assertAssignment('22', 'VI-AL-MAJID', 'BAR', 2);
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
