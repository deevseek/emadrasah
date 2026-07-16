<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Models\AssessmentComponent;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesAcademicTestData;
use Tests\TestCase;

class StudentScoreTest extends TestCase
{
    use CreatesAcademicTestData;
    use RefreshDatabase;

    public function test_bulk_score_remedial_final_predicate_and_class_validation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createPredicateRanges();
        [$year, $semester] = $this->createActiveAcademicPeriod();
        $teacher = $this->createTeacher($user, EmploymentType::SubjectTeacher);
        $classroom = $this->createClassroom($year, $this->createGradeLevel());
        $subject = $this->createSubject();
        $assignment = $this->createTeachingAssignment($year, $semester, $teacher, $classroom, $subject);
        $component = AssessmentComponent::query()->create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'teaching_assignment_id' => $assignment->id, 'employee_id' => $teacher->id, 'name' => 'Tugas', 'type' => 'assignment', 'weight' => 100, 'maximum_score' => 100, 'is_required' => true, 'created_by' => $user->id]);
        $student = $this->createActiveStudent();
        $this->createEnrollment($student, $year, $classroom);

        app(AssessmentService::class)->storeScores($component, [$student->id => ['score' => 80, 'remedial_score' => 95]], $user->id);
        app(AssessmentService::class)->storeScores($component, [$student->id => ['score' => 82, 'remedial_score' => 95]], $user->id);

        $this->assertDatabaseHas('student_scores', ['student_id' => $student->id, 'final_score' => 95, 'predicate' => 'A']);
        $this->assertDatabaseHas('activity_log', ['event' => 'assessment.scores.saved']);

        $other = $this->createActiveStudent();
        $this->expectException(ValidationException::class);
        app(AssessmentService::class)->storeScores($component, [$other->id => ['score' => 80]], $user->id);
    }
}
