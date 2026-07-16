<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Models\BtaqGroup;
use App\Models\BtaqLevel;
use App\Models\User;
use App\Services\BtaqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesAcademicTestData;
use Tests\TestCase;

class BtaqGroupTest extends TestCase
{
    use CreatesAcademicTestData;
    use RefreshDatabase;

    public function test_capacity_and_duplicate_active_membership_are_validated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$year, $semester] = $this->createActiveAcademicPeriod();
        $employee = $this->createTeacher($user, EmploymentType::BtaqTeacher);
        $level = BtaqLevel::query()->create(['code' => $this->uniqueSuffix('BTQ'), 'name' => 'Pra Iqra', 'sequence' => 1, 'is_active' => true]);
        $group = BtaqGroup::query()->create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'name' => 'Kelompok A', 'code' => $this->uniqueSuffix('KEL'), 'employee_id' => $employee->id, 'btaq_level_id' => $level->id, 'capacity' => 1, 'is_active' => true]);
        $classroom = $this->createClassroom($year, $this->createGradeLevel());
        $student = $this->createActiveStudent();
        $this->createEnrollment($student, $year, $classroom);

        app(BtaqService::class)->addMembers($group, [$student->id], $user->id);

        $this->assertDatabaseHas('btaq_group_students', ['student_id' => $student->id, 'status' => 'active']);
        $this->assertDatabaseHas('activity_log', ['event' => 'btaq.members.updated']);
        $this->expectException(ValidationException::class);

        app(BtaqService::class)->addMembers($group, [$student->id], $user->id);
    }
}
