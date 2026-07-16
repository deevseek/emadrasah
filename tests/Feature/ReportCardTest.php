<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Models\AssessmentComponent;
use App\Models\Extracurricular;
use App\Models\ReportCardSubject;
use App\Models\StudentAchievement;
use App\Models\StudentAttitudeNote;
use App\Models\StudentExtracurricular;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\ReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\Support\CreatesAcademicTestData;
use Tests\TestCase;

class ReportCardTest extends TestCase
{
    use CreatesAcademicTestData;
    use RefreshDatabase;

    public function test_generate_idempotent_submit_approve_lock_reopen_and_print(): void
    {
        foreach (['report-cards.view', 'report-cards.submit', 'report-cards.approve', 'report-cards.lock', 'report-cards.reopen', 'report-cards.print', 'report-cards.update'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['report-cards.view', 'report-cards.submit', 'report-cards.approve', 'report-cards.lock', 'report-cards.reopen', 'report-cards.print', 'report-cards.update']);
        $this->actingAs($user);
        $this->createPredicateRanges();
        [$year, $semester] = $this->createActiveAcademicPeriod();
        $homeroom = $this->createTeacher($user, EmploymentType::ClassTeacher);
        $classroom = $this->createClassroom($year, $this->createGradeLevel(), $homeroom);
        $subject = $this->createSubject();
        $assignment = $this->createTeachingAssignment($year, $semester, $homeroom, $classroom, $subject);
        $student = $this->createActiveStudent();
        $enrollment = $this->createEnrollment($student, $year, $classroom);
        $component = AssessmentComponent::query()->create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'teaching_assignment_id' => $assignment->id, 'employee_id' => $homeroom->id, 'name' => 'NA', 'type' => 'final_exam', 'weight' => 100, 'maximum_score' => 100, 'is_required' => true, 'created_by' => $user->id]);

        app(AssessmentService::class)->storeScores($component, [$student->id => ['score' => 88]], $user->id);
        StudentAttitudeNote::query()->create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id, 'entered_by' => $user->id, 'general_notes' => 'Baik']);
        $extra = Extracurricular::query()->create(['code' => $this->uniqueSuffix('EXT'), 'name' => 'Pramuka', 'is_active' => true]);
        StudentExtracurricular::query()->create(['student_id' => $student->id, 'extracurricular_id' => $extra->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id, 'predicate' => 'A', 'is_active' => true]);
        StudentAchievement::query()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id, 'achievement_type' => 'academic', 'name' => 'Juara', 'level' => 'Kelas', 'created_by' => $user->id]);

        $card = app(ReportCardService::class)->generate($enrollment, $semester->id);
        $again = app(ReportCardService::class)->generate($enrollment, $semester->id);

        $this->assertEquals($card->id, $again->id);
        $this->assertDatabaseCount('report_cards', 1);
        $this->assertSame(1, ReportCardSubject::query()->where('report_card_id', $card->id)->where('subject_id', $subject->id)->count());
        $this->assertDatabaseCount('report_card_btaq', 1);
        $this->assertDatabaseHas('report_card_btaq', [
            'report_card_id' => $card->id,
        ]);
        $this->patch(route('report-cards.submit', $card))->assertRedirect();
        $this->patch(route('report-cards.approve', $card))->assertRedirect();
        $this->patch(route('report-cards.lock', $card))->assertRedirect();
        $this->put(route('report-cards.update', $card), ['general_notes' => 'Terkunci'])->assertForbidden();

        $card->refresh();

        try {
            app(ReportCardService::class)->generate($enrollment, $semester->id);
            $this->fail('Rapor terkunci tidak boleh digenerate ulang.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->patch(route('report-cards.reopen', $card))->assertSessionHasErrors('reason');
        $this->patch(route('report-cards.reopen', $card), ['reason' => 'Perbaikan'])->assertRedirect();
        $this->get(route('report-cards.print', $card))->assertOk()->assertSee('Rapor Siswa');
        $this->assertDatabaseHas('report_card_status_histories', ['report_card_id' => $card->id, 'to_status' => 'reopened']);
        $this->assertDatabaseHas('activity_log', ['event' => 'report-card.generated']);
        $this->assertDatabaseHas('activity_log', ['event' => 'report-card.status']);
    }
}
