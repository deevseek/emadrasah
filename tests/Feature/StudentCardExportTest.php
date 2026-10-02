<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Student, User};
use App\Services\Personnel\SimpleXlsxService;
use App\Services\Students\StudentCardExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentCardExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_xlsx_contains_filtered_student_data_and_matching_photo_filename(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('student-photos/nested/foto.JPG', 'photo');
        $user = $this->user(['students.export', 'students.view-sensitive']);

        Student::create(['full_name' => 'Ahmad Fauzan', 'nisn' => '0012345678', 'birth_date' => '2012-05-17', 'address' => 'Jalan Melati 10', 'gender' => 'male', 'status' => 'active', 'classroom_label' => 'VII A', 'photo_path' => 'student-photos/nested/foto.JPG']);
        Student::create(['full_name' => 'Siti Aminah', 'nisn' => '0098765432', 'gender' => 'female', 'status' => 'active', 'classroom_label' => 'VII B']);

        $response = $this->actingAs($user)->get(route('students.export-card', ['classroom_label' => 'VII A']))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $rows = app(SimpleXlsxService::class)->read($path)['Data Kartu Siswa'];

        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertDownload('data-kartu-siswa-'.now()->format('Y-m-d-Hi').'.xlsx');
        $this->assertSame(StudentCardExportService::HEADERS, array_values($rows[1]));
        $this->assertSame(['Ahmad Fauzan', '0012345678', '17/05/2012', 'Jalan Melati 10', '0012345678.jpg'], array_values($rows[2]));
        $this->assertCount(2, $rows);
    }

    public function test_card_xlsx_hides_address_without_sensitive_data_permission(): void
    {
        $user = $this->user(['students.export']);
        Student::create(['full_name' => 'Ahmad Fauzan', 'nisn' => '0012345678', 'address' => 'Alamat Rahasia', 'gender' => 'male', 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('students.export-card'))->assertOk();
        $rows = app(SimpleXlsxService::class)->read($response->baseResponse->getFile()->getPathname())['Data Kartu Siswa'];

        $this->assertSame('Data disembunyikan', $rows[2][4]);
        $this->assertNotContains('Alamat Rahasia', $rows[2]);
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
