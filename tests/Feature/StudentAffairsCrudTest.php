<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdmissionType;
use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentDocumentType;
use App\Enums\StudentStatus;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\CreatesAcademicTestData;
use Tests\TestCase;

final class StudentAffairsCrudTest extends TestCase
{
    use CreatesAcademicTestData;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@example.test')->firstOrFail();
    }

    public function test_student_guardian_enrollment_document_and_status_crud_workflow(): void
    {
        Storage::fake('public');
        [$year] = $this->createActiveAcademicPeriod();
        $classroom = $this->createClassroom($year, $this->createGradeLevel());

        $studentPayload = [
            'student_number' => 'NIS-CRUD-1',
            'national_student_number' => 'NISN-CRUD-1',
            'name' => 'Siswa CRUD',
            'gender' => Gender::Male->value,
            'admission_type' => AdmissionType::NewStudent->value,
            'student_status' => StudentStatus::Active->value,
            'is_active' => 1,
        ];

        $this->actingAs($this->admin)->get(route('students.index'))->assertOk()->assertSee(route('students.create'));
        $this->get(route('students.create'))->assertOk()->assertSee('Simpan');
        $this->post(route('students.store'), $studentPayload + ['photo' => UploadedFile::fake()->image('siswa.jpg')])->assertRedirect();
        $student = Student::where('student_number', 'NIS-CRUD-1')->firstOrFail();
        $this->get(route('students.show', $student))->assertOk();
        $this->get(route('students.edit', $student))->assertOk();
        $this->put(route('students.update', $student), $studentPayload + ['name' => 'Siswa CRUD Update'])->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Siswa CRUD Update']);
        $this->post(route('students.store'), [])->assertSessionHasErrors('name');

        $guardianPayload = ['name' => 'Wali CRUD', 'gender' => Gender::Female->value, 'phone' => '08123456789', 'email' => 'wali@example.test', 'is_active' => 1];
        $this->get(route('guardians.index'))->assertOk()->assertSee(route('guardians.create'));
        $this->get(route('guardians.create'))->assertOk();
        $this->post(route('guardians.store'), $guardianPayload)->assertRedirect();
        $guardian = Guardian::where('email', 'wali@example.test')->firstOrFail();
        $this->get(route('guardians.show', $guardian))->assertOk();
        $this->get(route('guardians.edit', $guardian))->assertOk();
        $this->put(route('guardians.update', $guardian), $guardianPayload + ['phone' => '08999999999'])->assertRedirect();
        $this->assertDatabaseHas('guardians', ['id' => $guardian->id, 'phone' => '08999999999']);
        $this->post(route('guardians.store'), ['name' => 'Email Salah', 'email' => 'invalid'])->assertSessionHasErrors('email');

        $this->post(route('students.guardians.store', $student), ['guardian_id' => $guardian->id, 'relationship' => GuardianRelationship::Mother->value, 'is_primary' => 1])->assertRedirect();
        $this->assertDatabaseHas('guardian_student', ['student_id' => $student->id, 'guardian_id' => $guardian->id]);
        $this->put(route('students.guardians.update', [$student, $guardian]), ['guardian_id' => $guardian->id, 'relationship' => GuardianRelationship::Father->value, 'is_primary' => 0])->assertRedirect();
        $this->delete(route('students.guardians.destroy', [$student, $guardian]))->assertRedirect();
        $this->assertDatabaseMissing('guardian_student', ['student_id' => $student->id, 'guardian_id' => $guardian->id]);

        $this->get(route('student-enrollments.index'))->assertOk();
        $this->get(route('student-enrollments.create'))->assertOk();
        $this->post(route('student-enrollments.store'), ['student_id' => $student->id, 'academic_year_id' => $year->id, 'classroom_id' => $classroom->id])->assertRedirect();
        $enrollment = StudentEnrollment::where('student_id', $student->id)->firstOrFail();
        $this->post(route('student-enrollments.store'), ['student_id' => $student->id, 'academic_year_id' => $year->id, 'classroom_id' => $classroom->id])->assertSessionHasErrors();
        $targetClassroom = $this->createClassroom($year, $this->createGradeLevel());
        $this->post(route('student-enrollments.transfer', $enrollment), ['classroom_id' => $targetClassroom->id])->assertRedirect();
        $this->delete(route('student-enrollments.destroy', $enrollment->fresh()))->assertRedirect();

        $this->post(route('students.status.store', $student), ['student_status' => StudentStatus::Transferred->value, 'effective_date' => '2026-07-16', 'reason' => 'Pindah'])->assertRedirect();
        $this->assertDatabaseHas('student_status_histories', ['student_id' => $student->id, 'student_status' => StudentStatus::Transferred->value]);

        $file = UploadedFile::fake()->create('akta.pdf', 10, 'application/pdf');
        $this->post(route('students.documents.store', $student), ['document_type' => StudentDocumentType::BirthCertificate->value, 'file' => $file])->assertRedirect();
        $document = StudentDocument::where('student_id', $student->id)->firstOrFail();
        Storage::disk('public')->assertExists($document->file_path);
        $this->get(route('student-documents.download', $document))->assertOk();
        $this->delete(route('student-documents.destroy', $document))->assertRedirect();
        Storage::disk('public')->assertMissing($document->file_path);

        $this->delete(route('students.destroy', $student))->assertRedirect();
        $this->assertGreaterThan(0, Activity::count());
    }

    public function test_student_affairs_routes_forbid_users_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('students.index'))->assertForbidden();
        $this->actingAs($user)->get(route('guardians.index'))->assertForbidden();
    }
}
