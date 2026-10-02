<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Student, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentCardExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_csv_contains_filtered_student_data_and_matching_photo_filename(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('student-photos/nested/foto.JPG', 'photo');
        $user = $this->user(['students.export', 'students.view-sensitive']);

        Student::create(['full_name' => 'Ahmad Fauzan', 'nisn' => '0012345678', 'birth_date' => '2012-05-17', 'address' => 'Jalan Melati 10', 'gender' => 'male', 'status' => 'active', 'classroom_label' => 'VII A', 'photo_path' => 'student-photos/nested/foto.JPG']);
        Student::create(['full_name' => 'Siti Aminah', 'nisn' => '0098765432', 'gender' => 'female', 'status' => 'active', 'classroom_label' => 'VII B']);

        $response = $this->actingAs($user)->get(route('students.export-card', ['classroom_label' => 'VII A']))->assertOk();
        $contents = file_get_contents($response->baseResponse->getFile()->getPathname());

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contents);
        $this->assertStringContainsString('NAMA_LENGKAP,NISN,TANGGAL_LAHIR,ALAMAT,FOTO', $contents);
        $this->assertStringContainsString('"Ahmad Fauzan",0012345678,17/05/2012,"Jalan Melati 10",0012345678.jpg', $contents);
        $this->assertStringNotContainsString('Siti Aminah', $contents);
    }

    public function test_card_csv_hides_address_without_sensitive_data_permission(): void
    {
        $user = $this->user(['students.export']);
        Student::create(['full_name' => 'Ahmad Fauzan', 'nisn' => '0012345678', 'address' => 'Alamat Rahasia', 'gender' => 'male', 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('students.export-card'))->assertOk();
        $contents = file_get_contents($response->baseResponse->getFile()->getPathname());

        $this->assertStringContainsString('Data disembunyikan', $contents);
        $this->assertStringNotContainsString('Alamat Rahasia', $contents);
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        return $user;
    }
}
