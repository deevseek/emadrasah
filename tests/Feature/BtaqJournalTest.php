<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Models\BtaqGroup;
use App\Models\BtaqJournal;
use App\Models\BtaqLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\CreatesAcademicTestData;
use Tests\TestCase;

class BtaqJournalTest extends TestCase
{
    use CreatesAcademicTestData;
    use RefreshDatabase;

    public function test_mass_journal_submit_verify_and_reject(): void
    {
        foreach (['btaq-journals.view-own', 'btaq-journals.submit', 'btaq-journals.verify', 'btaq-journals.reject'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['btaq-journals.view-own', 'btaq-journals.submit', 'btaq-journals.verify', 'btaq-journals.reject']);
        [$year, $semester] = $this->createActiveAcademicPeriod();
        $employee = $this->createTeacher($user, EmploymentType::BtaqTeacher);
        $level = BtaqLevel::query()->create(['code' => $this->uniqueSuffix('BTQ'), 'name' => 'Level BTAQ', 'sequence' => 1, 'is_active' => true]);
        $group = BtaqGroup::query()->create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'name' => 'Kelompok BTAQ', 'code' => $this->uniqueSuffix('KEL'), 'employee_id' => $employee->id, 'btaq_level_id' => $level->id, 'is_active' => true]);
        $student = $this->createActiveStudent();
        $this->createEnrollment($student, $year, $this->createClassroom($year, $this->createGradeLevel()));
        $group->students()->create(['student_id' => $student->id, 'joined_at' => '2026-07-01', 'status' => 'active']);

        $payload = ['btaq_group_id' => $group->id, 'journal_date' => '2026-07-16', 'session_number' => 1];
        $students = ['students' => [$student->id => ['attendance_status' => 'hadir', 'progress_status' => 'needs_guidance', 'reading_score' => 80]]];

        $response = $this->actingAs($user)->post(route('btaq-journals.store'), $payload + $students);
        $journal = BtaqJournal::query()->where('btaq_group_id', $group->id)->firstOrFail();

        $response->assertRedirect(route('btaq-journals.show', $journal));
        $this->assertDatabaseHas('btaq_journal_students', ['btaq_journal_id' => $journal->id, 'student_id' => $student->id]);
        $this->patch(route('btaq-journals.submit', $journal))->assertRedirect();
        $this->patch(route('btaq-journals.verify', $journal))->assertRedirect();

        $rejectResponse = $this->actingAs($user)->post(route('btaq-journals.store'), ['btaq_group_id' => $group->id, 'journal_date' => '2026-07-17', 'session_number' => 1] + $students);
        $rejectJournal = BtaqJournal::query()->where('btaq_group_id', $group->id)->whereDate('journal_date', '2026-07-17')->firstOrFail();
        $rejectResponse->assertRedirect(route('btaq-journals.show', $rejectJournal));
        $this->patch(route('btaq-journals.submit', $rejectJournal))->assertRedirect();
        $this->patch(route('btaq-journals.reject', $rejectJournal))->assertSessionHasErrors('rejection_reason');
        $this->patch(route('btaq-journals.reject', $rejectJournal), ['rejection_reason' => 'Perbaiki catatan.'])->assertRedirect();
        $this->assertDatabaseHas('activity_log', ['event' => 'btaq.journal.saved']);
    }
}
