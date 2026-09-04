<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ConsultationMessageSent;
use App\Models\{AcademicYear, Classroom, ClassroomMembership, GradeLevel, GuardianProfile, Personnel, Student, StudentGuardian, TeacherConsultation, User};
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TeacherConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_message_is_routed_to_the_assigned_homeroom_teacher(): void
    {
        Event::fake([ConsultationMessageSent::class]);
        [$parent, $teacher, $student] = $this->scenario();

        $this->actingAs($parent)->post(route('parent.consultation.store', $student), ['message' => 'Mohon informasi perkembangan belajar.'])->assertRedirect();

        $consultation = TeacherConsultation::firstOrFail();
        $this->assertSame($teacher->id, $consultation->teacher_user_id);
        $this->assertDatabaseHas('teacher_consultation_messages', ['sender_user_id' => $parent->id, 'body' => 'Mohon informasi perkembangan belajar.']);
        Event::assertDispatched(ConsultationMessageSent::class);

        $this->actingAs($teacher)->get(route('consultations.index'))->assertOk()->assertSee($student->full_name)->assertSee('1 baru');
        $this->post(route('consultations.store', $consultation), ['message' => 'Perkembangannya baik.'])->assertRedirect();
        $this->assertDatabaseHas('teacher_consultation_messages', ['sender_user_id' => $teacher->id, 'body' => 'Perkembangannya baik.']);
    }

    public function test_other_teacher_cannot_read_or_reply_to_the_conversation(): void
    {
        [$parent, , $student] = $this->scenario();
        $this->actingAs($parent)->post(route('parent.consultation.store', $student), ['message' => 'Pesan privat.']);
        $consultation = TeacherConsultation::firstOrFail();
        $other = User::factory()->create(['must_change_password' => false]);
        $other->assignRole('guru');

        $this->actingAs($other)->get(route('consultations.show', $consultation))->assertForbidden();
        $this->post(route('consultations.store', $consultation), ['message' => 'Tidak berhak'])->assertForbidden();
    }

    private function scenario(): array
    {
        $this->seed(AccessControlSeeder::class);
        $parent = User::factory()->create(['must_change_password' => false]); $parent->assignRole('orang-tua');
        $teacher = User::factory()->create(['name' => 'Ustazah Wali Kelas', 'must_change_password' => false]); $teacher->assignRole('guru');
        $personnel = Personnel::create(['user_id' => $teacher->id, 'full_name' => 'Ustazah Wali Kelas', 'gender' => 'female', 'employment_status' => 'permanent', 'position' => 'Guru', 'is_active' => true]);
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
        $grade = GradeLevel::create(['number' => 1, 'name' => 'Kelas 1', 'roman_label' => 'I', 'sort_order' => 1]);
        $classroom = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'code' => 'I A', 'homeroom_personnel_id' => $personnel->id, 'is_active' => true]);
        $student = Student::create(['full_name' => 'Ananda Saleh', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'status' => 'active', 'joined_at' => now()->toDateString()]);
        $guardian = GuardianProfile::create(['user_id' => $parent->id, 'name' => 'Wali Ananda', 'is_active' => true]);
        StudentGuardian::create(['guardian_id' => $guardian->id, 'student_id' => $student->id, 'relationship' => 'Orang Tua', 'can_view_academic' => true]);

        return [$parent, $teacher, $student];
    }
}
