<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Student, User};
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class StudentPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_photo_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('local');
        $user = $this->user(['students.create', 'students.update', 'students.view']);

        $this->actingAs($user)->post(route('students.store'), $this->studentData([
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]))->assertSessionHasNoErrors();

        $student = Student::firstOrFail();
        $this->assertSame('student-photos/1000000001.jpg', $student->photo_path);
        Storage::disk('local')->assertExists($student->photo_path);
        $oldPath = $student->photo_path;

        $this->actingAs($user)->put(route('students.update', $student), $this->studentData([
            'photo' => UploadedFile::fake()->image('pengganti.png'),
        ]))->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('student-photos/1000000001.png', $student->photo_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($student->photo_path);
        $this->actingAs($user)->get(route('students.photo', $student))->assertOk();
    }

    public function test_teacher_role_can_capture_and_upload_student_photo_from_show_page(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $teacher = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $teacher->assignRole('guru');
        $student = Student::create($this->studentData());

        $this->actingAs($teacher)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('capture="environment"', false)
            ->assertSee('data-student-photo-input', false)
            ->assertSee('akan dikompresi otomatis')
            ->assertSee(route('students.photo.update', $student), false);

        $this->post(route('students.photo.update', $student), [
            'photo' => UploadedFile::fake()->image('kamera-belakang.webp'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('student-photos/1000000001.webp', $student->fresh()->photo_path);
        Storage::disk('local')->assertExists('student-photos/1000000001.webp');
    }

    public function test_photo_cannot_be_uploaded_until_student_has_nisn(): void
    {
        Storage::fake('local');
        $user = $this->user(['students.photo.manage']);
        $student = Student::create($this->studentData(['nisn' => null]));

        $this->actingAs($user)->post(route('students.photo.update', $student), [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($student->fresh()->photo_path);
        Storage::disk('local')->assertDirectoryEmpty('student-photos');
    }

    public function test_photo_is_optional_and_invalid_upload_is_rejected(): void
    {
        $user = $this->user(['students.create']);
        $this->actingAs($user)->post(route('students.store'), $this->studentData())->assertSessionHasNoErrors();
        $this->assertNull(Student::firstOrFail()->photo_path);

        $this->actingAs($user)->post(route('students.store'), $this->studentData([
            'nisn' => '1000000002',
            'photo' => UploadedFile::fake()->create('dokumen.pdf', 50, 'application/pdf'),
        ]))->assertSessionHasErrors('photo');
    }

    public function test_class_photo_archive_uses_each_students_nisn_as_filename(): void
    {
        Storage::fake('local');
        $user = $this->user(['students.export']);
        foreach (['1000000001' => 'jpg', '1000000002' => 'png'] as $nisn => $extension) {
            $path = "student-photos/{$nisn}/photo.{$extension}";
            Storage::disk('local')->put($path, "photo-{$nisn}");
            Student::create(['full_name' => "Siswa {$nisn}", 'nisn' => $nisn, 'gender' => 'male', 'status' => 'active', 'classroom_label' => 'VII A', 'photo_path' => $path]);
        }
        Student::create(['full_name' => 'Kelas Lain', 'nisn' => '1000000003', 'gender' => 'female', 'status' => 'active', 'classroom_label' => 'VII B']);

        $response = $this->actingAs($user)->get(route('students.photos.download', ['classroom_label' => 'VII A']))->assertOk();
        $temporaryZip = tempnam(sys_get_temp_dir(), 'zip-test-');
        copy($response->baseResponse->getFile()->getPathname(), $temporaryZip);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($temporaryZip) === true);
        $this->assertNotFalse($zip->locateName('1000000001.jpg'));
        $this->assertNotFalse($zip->locateName('1000000002.png'));
        $this->assertFalse($zip->locateName('1000000003.jpg'));
        $zip->close();
        @unlink($temporaryZip);
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) $user->givePermissionTo(Permission::findOrCreate($permission));
        return $user;
    }

    private function studentData(array $overrides = []): array
    {
        return array_merge(['full_name' => 'Ahmad Fauzan', 'nisn' => '1000000001', 'gender' => 'male', 'status' => 'active', 'classroom_label' => 'VII A'], $overrides);
    }
}
