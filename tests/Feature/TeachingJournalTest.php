<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\SubjectCategory;
use App\Enums\TeachingJournalStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TeachingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final 
class TeachingJournalTest extends TestCase
{
    use RefreshDatabase;

    private bool $seeded = false;

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
        $assignment = $this->assignmentForTeacher();
        $journal = TeachingJournal::query()->create(array_replace($this->payload($assignment, TeachingJournalStatus::Submitted->value), [
            'employee_id' => $assignment->employee_id,
            'subject_id' => $assignment->subject_id,
            'classroom_id' => $assignment->classroom_id,
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $assignment->semester_id,
        ]));

        $this->actingAs($admin)->patch(route('teaching-journals.reject', $journal))->assertSessionHasErrors('rejection_reason');
        $this->actingAs($admin)->patch(route('teaching-journals.verify', $journal))->assertSessionHas('status');
        $this->actingAs($admin)->get(route('teaching-journals.print', $journal))->assertOk();
    }

    private function admin(): User
    {
        $this->seedOnce();

        return User::query()->where('email', 'admin@example.test')->firstOrFail();
    }

    private function assignmentForTeacher(array $permissions = [
        'teaching-journals.view-own',
        'teaching-journals.create',
        'teaching-journals.update',
        'teaching-journals.submit',
    ]): TeachingAssignment
    {
        $this->seedOnce();

        $suffix = Str::upper(Str::random(8));
        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->firstOrFail();
        $semester = Semester::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('is_active', true)
            ->firstOrFail();
        $gradeLevel = GradeLevel::query()
            ->where('level', 1)
            ->firstOrFail();
        $classroom = Classroom::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Rombel Uji '.$suffix,
            'code' => 'CL-'.$suffix,
            'capacity' => 30,
            'is_active' => true,
        ]);
        $subject = Subject::query()->create([
            'code' => 'SB-'.$suffix,
            'name' => 'Mapel Uji '.$suffix,
            'category' => SubjectCategory::General,
            'minimum_passing_grade' => 75,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['is_active' => true]);
        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'employee_number' => 'TEST-'.$suffix,
            'name' => 'Guru Jurnal Pengujian',
            'gender' => Gender::Male,
            'employment_type' => EmploymentType::ClassTeacher,
            'employee_status' => EmployeeStatus::Permanent,
            'is_active' => true,
        ]);

        $user->givePermissionTo($permissions);

        return TeachingAssignment::query()->create([
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'employee_id' => $employee->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'weekly_hours' => 4,
            'is_active' => true,
        ])->fresh('employee.user', 'subject', 'classroom');
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

    private function seedOnce(): void
    {
        if ($this->seeded) {
            return;
        }

        $this->seed();
        $this->seeded = true;
    }
}
