<?php

declare(strict_types=1);

namespace Tests\Feature\StudentAffairs;

use App\Enums\AdmissionType;
use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentDocumentType;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentAffairsTest extends TestCase
{
    use RefreshDatabase;

    private array $permissions = [
        'students.view', 'students.create', 'students.update', 'students.delete', 'students.change-status', 'students.manage-documents',
        'guardians.view', 'guardians.create', 'guardians.update', 'guardians.delete',
        'student-guardians.view', 'student-guardians.create', 'student-guardians.update', 'student-guardians.delete',
        'student-enrollments.view', 'student-enrollments.create', 'student-enrollments.update', 'student-enrollments.delete', 'student-enrollments.transfer',
    ];

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@example.test')->firstOrFail();
    }

    private function studentData(array $overrides = []): array
    {
        return $overrides + [
            'student_number' => 'S-1001',
            'national_student_number' => 'NISN-1001',
            'national_identity_number' => 'NIK-S-1001',
            'name' => 'Siswa Uji',
            'gender' => Gender::Male->value,
            'admission_type' => AdmissionType::NewStudent->value,
            'student_status' => StudentStatus::Active->value,
            'birth_date' => '2017-01-01',
            'admission_date' => '2026-07-01',
        ];
    }

    private function guardianData(array $overrides = []): array
    {
        return $overrides + [
            'national_identity_number' => 'NIK-W-1001',
            'name' => 'Wali Uji',
            'gender' => Gender::Female->value,
            'monthly_income' => 0,
            'phone' => '081234567890',
            'email' => 'wali@example.test',
        ];
    }

    private function classroom(int $capacity = 2): Classroom
    {
        $year = AcademicYear::firstOrCreate(['name' => '2026/2027'], ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $grade = GradeLevel::query()->where('level', 1)->firstOrFail();

        $suffix = str_pad((string) $capacity, 2, '0', STR_PAD_LEFT).'-'.str()->random(6);

        return Classroom::create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => 'Uji '.$suffix,
            'code' => 'UJI-'.$suffix,
            'capacity' => $capacity,
            'is_active' => true,
        ]);
    }

    private function fakePng(string $name = 'foto.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    public function test_permissions_and_forbidden_access(): void
    {
        $admin = $this->admin();

        foreach ($this->permissions as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
            $this->assertTrue($admin->can($permission));
        }

        $this->actingAs(User::factory()->create())->get(route('students.index'))->assertForbidden();
    }

    public function test_student_crud_uniqueness_photo_soft_delete_and_logs(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create();
        $payload = $this->studentData(['user_id' => $user->id, 'photo' => $this->fakePng('foto.png')]);
        $this->post(route('students.store'), $payload)->assertRedirect();
        $student = Student::where('student_number', 'S-1001')->firstOrFail();
        Storage::disk('public')->assertExists($student->photo_path);

        $this->post(route('students.store'), $this->studentData(['student_number' => 'S-1001', 'national_student_number' => 'NISN-X', 'national_identity_number' => 'NIK-X']))->assertSessionHasErrors('student_number');
        $this->post(route('students.store'), $this->studentData(['student_number' => 'S-X', 'national_student_number' => 'NISN-1001', 'national_identity_number' => 'NIK-Y']))->assertSessionHasErrors('national_student_number');
        $this->post(route('students.store'), $this->studentData(['student_number' => 'S-Y', 'national_student_number' => 'NISN-Y', 'national_identity_number' => 'NIK-S-1001']))->assertSessionHasErrors('national_identity_number');
        $this->post(route('students.store'), $this->studentData(['student_number' => 'S-Z', 'national_student_number' => 'NISN-Z', 'national_identity_number' => 'NIK-Z', 'user_id' => $user->id]))->assertSessionHasErrors('user_id');
        $this->post(route('students.store'), $this->studentData(['student_number' => 'S-BAD', 'national_student_number' => 'NISN-BAD', 'national_identity_number' => 'NIK-BAD', 'photo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('photo');

        $oldPhoto = $student->photo_path;
        $this->put(route('students.update', $student), $this->studentData(['name' => 'Siswa Diperbarui', 'photo' => $this->fakePng('baru.png')]))->assertRedirect();
        $student->refresh();
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertExists($student->photo_path);

        $this->delete(route('students.destroy', $student))->assertRedirect();
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertTrue(Activity::whereIn('event', ['student.created', 'student.updated', 'student.deleted'])->count() >= 3);
    }

    public function test_guardian_crud_relationship_rules_and_logs(): void
    {
        $this->actingAs($this->admin());
        $student = Student::create($this->studentData());
        $studentTwo = Student::create($this->studentData(['student_number' => 'S-1002', 'national_student_number' => 'NISN-1002', 'national_identity_number' => 'NIK-S-1002']));
        $user = User::factory()->create();

        $this->post(route('guardians.store'), $this->guardianData(['user_id' => $user->id]))->assertRedirect();
        $guardian = Guardian::where('national_identity_number', 'NIK-W-1001')->firstOrFail();
        $this->post(route('guardians.store'), $this->guardianData(['national_identity_number' => 'NIK-W-1001', 'email' => 'x@example.test']))->assertSessionHasErrors('national_identity_number');
        $this->post(route('guardians.store'), $this->guardianData(['national_identity_number' => 'NIK-W-X', 'user_id' => $user->id]))->assertSessionHasErrors('user_id');

        $attach = ['guardian_id' => $guardian->id, 'relationship' => GuardianRelationship::Mother->value, 'is_primary' => true, 'is_emergency_contact' => true, 'lives_with_student' => true, 'financially_responsible' => true];
        $this->post(route('students.guardians.store', $student), $attach)->assertRedirect();
        $this->post(route('students.guardians.store', $studentTwo), $attach)->assertRedirect();
        $this->post(route('students.guardians.store', $student), $attach)->assertSessionHasErrors('guardian_id');

        $other = Guardian::create($this->guardianData(['national_identity_number' => 'NIK-W-2', 'name' => 'Wali Kedua', 'email' => 'wali2@example.test']));
        $otherAttach = array_replace($attach, [
            'guardian_id' => $other->id,
        ]);

        $this->assertNotSame($guardian->id, $other->id);
        $this->assertSame($other->id, $otherAttach['guardian_id']);

        $this->post(route('students.guardians.store', $student), $otherAttach)
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $student->refresh();
        $student->unsetRelation('guardians');
        $studentTwo->refresh();
        $studentTwo->unsetRelation('guardians');
        $this->assertSame(1, $student->guardians()->wherePivot('is_primary', true)->count());
        $this->assertTrue((bool) $student->guardians()->whereKey($other->id)->firstOrFail()->pivot->is_primary);
        $this->assertFalse((bool) $student->guardians()->whereKey($guardian->id)->firstOrFail()->pivot->is_primary);
        $this->assertTrue((bool) $studentTwo->guardians()->whereKey($guardian->id)->firstOrFail()->pivot->is_primary);
        $this->delete(route('guardians.destroy', $guardian))->assertRedirect();
        $this->assertSoftDeleted('guardians', ['id' => $guardian->id]);
        $this->assertTrue(Activity::where('event', 'like', 'guardian.%')->exists());
    }

    public function test_enrollment_status_documents_permissions_and_idempotent_seeder(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $classroom = $this->classroom(1);
        $student = Student::create($this->studentData());

        $payload = ['student_id' => $student->id, 'academic_year_id' => $classroom->academic_year_id, 'classroom_id' => $classroom->id, 'enrolled_at' => '2026-07-15'];
        $this->post(route('student-enrollments.store'), $payload)->assertRedirect();
        $this->post(route('student-enrollments.store'), $payload)->assertSessionHasErrors('student_id');

        $other = Student::create($this->studentData(['student_number' => 'S-1002', 'national_student_number' => 'NISN-1002', 'national_identity_number' => 'NIK-S-1002']));
        $otherPayload = array_replace($payload, [
            'student_id' => $other->id,
        ]);

        $this->assertNotSame($student->id, $other->id);
        $this->assertSame($other->id, $otherPayload['student_id']);
        $this->assertFalse($other->enrollments()->where('enrollment_status', EnrollmentStatus::Active)->exists());

        $response = $this->post(route('student-enrollments.store'), $otherPayload);

        $response->assertSessionHasErrors('classroom_id');
        $response->assertSessionDoesntHaveErrors('student_id');
        $other->update(['student_status' => StudentStatus::Inactive]);
        $openClass = $this->classroom(5);
        $this->post(route('student-enrollments.store'), ['student_id' => $other->id, 'academic_year_id' => $openClass->academic_year_id, 'classroom_id' => $openClass->id])->assertSessionHasErrors('student_id');

        $enrollment = StudentEnrollment::where('student_id', $student->id)->firstOrFail();
        $target = Classroom::create(['academic_year_id' => $classroom->academic_year_id, 'grade_level_id' => $classroom->grade_level_id, 'name' => '1B', 'code' => '1B', 'capacity' => 5, 'is_active' => true]);
        $this->post(route('student-enrollments.transfer', $enrollment), ['classroom_id' => $target->id, 'notes' => 'Pindah rombel'])->assertRedirect();
        $this->assertDatabaseHas('student_enrollments', ['id' => $enrollment->id, 'enrollment_status' => EnrollmentStatus::Transferred->value]);

        $this->post(route('students.status.store', $student), ['new_status' => StudentStatus::Withdrawn->value, 'effective_date' => '2026-08-01', 'reason' => 'Keluar'])->assertRedirect();
        $this->assertDatabaseHas('student_status_histories', ['student_id' => $student->id, 'new_status' => StudentStatus::Withdrawn->value]);
        $this->assertDatabaseMissing('student_enrollments', ['student_id' => $student->id, 'enrollment_status' => EnrollmentStatus::Active->value]);

        $this->post(route('students.documents.store', $student), ['document_type' => StudentDocumentType::FamilyCard->value, 'file' => UploadedFile::fake()->create('kk.pdf', 10, 'application/pdf'), 'issued_at' => '2026-01-01', 'expires_at' => '2026-12-31'])->assertRedirect();
        $document = $student->documents()->firstOrFail();
        $this->get(route('student-documents.download', $document))->assertOk();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('students.view');
        $this->actingAs($viewer)->get(route('student-documents.download', $document))->assertForbidden();

        $this->actingAs(User::where('email', 'admin@example.test')->first());
        $this->post(route('students.documents.store', $student), ['document_type' => 'bad', 'file' => UploadedFile::fake()->create('kk.pdf', 10, 'application/pdf')])->assertSessionHasErrors('document_type');
        $this->post(route('students.documents.store', $student), ['document_type' => StudentDocumentType::FamilyCard->value, 'file' => UploadedFile::fake()->create('big.pdf', 5000, 'application/pdf')])->assertSessionHasErrors('file');
        $this->delete(route('student-documents.destroy', $document))->assertRedirect();

        $this->seed(\Database\Seeders\StudentAffairsSeeder::class);
        $firstCounts = [
            'students' => Student::count(),
            'guardians' => Guardian::count(),
            'enrollments' => StudentEnrollment::count(),
            'pivots' => DB::table('guardian_student')->count(),
        ];

        $this->seed(\Database\Seeders\StudentAffairsSeeder::class);
        $secondCounts = [
            'students' => Student::count(),
            'guardians' => Guardian::count(),
            'enrollments' => StudentEnrollment::count(),
            'pivots' => DB::table('guardian_student')->count(),
        ];

        $this->assertSame($firstCounts, $secondCounts);
        $this->assertTrue(Activity::whereIn('event', ['student.enrolled', 'student.status.changed', 'student.document.uploaded'])->count() >= 3);
    }
}
