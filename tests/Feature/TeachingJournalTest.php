<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TeachingJournalStatus;
use App\Models\TeachingAssignment;
use App\Models\TeachingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeachingJournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_submit_and_cannot_edit_submitted_journal(): void
    {
        $assignment = $this->assignmentForTeacher();
        $payload = $this->payload($assignment, TeachingJournalStatus::Draft->value);

        $response = $this->actingAs($assignment->employee->user)->post(route('teaching-journals.store'), $payload);
        $response->assertRedirect();

        $journal = TeachingJournal::query()->firstOrFail();
        $this->actingAs($assignment->employee->user)->patch(route('teaching-journals.submit', $journal))->assertSessionHas('status');
        $this->assertDatabaseHas('teaching_journals', ['id' => $journal->id, 'status' => TeachingJournalStatus::Submitted->value]);
        $this->actingAs($assignment->employee->user)->put(route('teaching-journals.update', $journal), $payload)->assertSessionHasErrors('status');
        $this->assertDatabaseHas('activity_log', ['event' => 'teaching-journal.submitted']);
    }

    public function test_verify_and_reject_workflow_requires_reason(): void
    {
        $admin = $this->admin();
        $assignment = TeachingAssignment::query()->firstOrFail();
        $journal = TeachingJournal::query()->create($this->payload($assignment, TeachingJournalStatus::Submitted->value) + [
            'employee_id' => $assignment->employee_id,
            'subject_id' => $assignment->subject_id,
            'classroom_id' => $assignment->classroom_id,
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $assignment->semester_id,
        ]);

        $this->actingAs($admin)->patch(route('teaching-journals.reject', $journal))->assertSessionHasErrors('rejection_reason');
        $this->actingAs($admin)->patch(route('teaching-journals.verify', $journal))->assertSessionHas('status');
        $this->actingAs($admin)->get(route('teaching-journals.print', $journal))->assertOk();
    }

    private function admin(): User
    {
        $this->seed();

        return User::query()->where('email', 'admin@example.test')->firstOrFail();
    }

    private function assignmentForTeacher(): TeachingAssignment
    {
        $this->seed();
        $assignment = TeachingAssignment::query()->whereHas('employee.user')->firstOrFail();
        $assignment->employee->user->givePermissionTo([
            'teaching-journals.view-own',
            'teaching-journals.create',
            'teaching-journals.update',
            'teaching-journals.submit',
        ]);

        return $assignment;
    }

    private function payload(TeachingAssignment $assignment, string $status): array
    {
        return [
            'teaching_assignment_id' => $assignment->id,
            'journal_date' => '2026-07-24',
            'starts_at' => '07:00',
            'ends_at' => '08:10',
            'lesson_hours' => 2,
            'material' => 'Materi pembelajaran inti.',
            'learning_objectives' => 'Siswa memahami tujuan pembelajaran.',
            'method' => 'Diskusi',
            'media' => 'Papan tulis',
            'assignment' => 'Latihan mandiri',
            'assessment' => 'Observasi',
            'teacher_notes' => 'Kelas berjalan tertib.',
            'status' => $status,
        ];
    }
}
