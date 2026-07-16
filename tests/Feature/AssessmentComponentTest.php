<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Models\AssessmentComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\CreatesAcademicTestData;
use Tests\TestCase;

class AssessmentComponentTest extends TestCase
{
    use CreatesAcademicTestData;
    use RefreshDatabase;

    public function test_component_weight_and_publish_workflow(): void
    {
        foreach (['assessments.view-own', 'assessments.create', 'assessments.publish'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['assessments.view-own', 'assessments.create', 'assessments.publish']);
        [$year, $semester] = $this->createActiveAcademicPeriod();
        $teacher = $this->createTeacher($user, EmploymentType::SubjectTeacher);
        $classroom = $this->createClassroom($year, $this->createGradeLevel());
        $subject = $this->createSubject();
        $assignment = $this->createTeachingAssignment($year, $semester, $teacher, $classroom, $subject);

        $this->actingAs($user)
            ->post(route('assessment-components.store'), [
                'teaching_assignment_id' => $assignment->id,
                'name' => 'UH 1',
                'type' => 'daily_test',
                'weight' => 40,
                'maximum_score' => 100,
                'is_required' => 1,
            ])
            ->assertRedirect();

        $component = AssessmentComponent::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('assessment-components.store'), [
                'teaching_assignment_id' => $assignment->id,
                'name' => 'UH Berlebih',
                'type' => 'daily_test',
                'weight' => 70,
                'maximum_score' => 100,
                'is_required' => 1,
            ])
            ->assertStatus(422);

        $this->patch(route('assessment-components.publish', $component))->assertRedirect();

        $this->assertEquals('published', $component->fresh()->status);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $component->id, 'event' => 'assessment.component.published']);
    }
}
