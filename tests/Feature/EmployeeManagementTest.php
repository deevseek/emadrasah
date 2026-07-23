<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Employee;
use App\Models\User;
use App\Services\Employee\EmployeeImportService;
use App\Services\Employee\EmployeeImportTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_search_filter_and_export_employees(): void
    {
        $admin = $this->userWith(['employees.view','employees.create','employees.update','employees.activate','employees.manage-documents','employees.link-account','employees.export']);
        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $payload = $this->payload(['nip' => '198001', 'nuptk' => 'NUPTK1', 'employee_number' => 'NIY1', 'national_identity_number' => 'NIK1']);
        $this->actingAs($admin)->post(route('employees.store'), $payload)->assertRedirect();
        $employee = Employee::firstOrFail();
        $this->assertDatabaseHas('activity_log', ['event' => 'employee.created']);
        $this->get(route('employees.index', ['search' => '198001']))->assertOk()->assertSee('Guru Tes');
        $this->get(route('employees.index', ['employment_type' => EmploymentType::ClassTeacher->value]))->assertOk()->assertSee('Guru Kelas');
        $this->get(route('employees.export', ['search' => '198001']))->assertOk()->assertDontSee('password')->assertDontSee('employee-documents');
        $this->get(route('employees.show', $employee))->assertOk()->assertSee('Guru Tes');
        $this->get(route('employees.edit', $employee))->assertOk()->assertSee('Guru Tes');
    }

    public function test_employee_import_accepts_common_birth_header_variations(): void
    {
        $service = new EmployeeImportService();
        $detectColumns = new ReflectionMethod($service, 'detectColumns');
        $detectColumns->setAccessible(true);
        $mapRow = new ReflectionMethod($service, 'mapRow');
        $mapRow->setAccessible(true);

        [, $combinedColumns] = $detectColumns->invoke($service, [
            1 => ['Nama Lengkap', 'L/P', "Tempat,\nTgl. Lahir", 'Status', 'Nomor Induk Yayasan (NIY)', 'Jabatan'],
        ]);

        $combinedPayload = $mapRow->invoke($service, [
            'Guru Format Gabungan', 'L', 'Demak, 1 Januari 1990', 'GTY', 'NIY-001', 'Guru Kelas',
        ], $combinedColumns);

        $this->assertSame('Demak', $combinedPayload['birth_place']);
        $this->assertSame('1990-01-01', $combinedPayload['birth_date']);

        [, $slashColumns] = $detectColumns->invoke($service, [
            1 => ['Nama Lengkap', 'L/P', 'Tempat/Tanggal Lahir', 'Status', 'Nomor Induk Yayasan (NIY)', 'Jabatan'],
        ]);

        $slashPayload = $mapRow->invoke($service, [
            'Guru Format Garis Miring', 'L', 'Demak, 2 Januari 1990', 'GTY', 'NIY-004', 'Guru Kelas',
        ], $slashColumns);

        $this->assertSame('Demak', $slashPayload['birth_place']);
        $this->assertSame('1990-01-02', $slashPayload['birth_date']);

        [, $splitColumns] = $detectColumns->invoke($service, [
            1 => ['Nama Lengkap', 'L/P', 'Tempat Lahir', 'Tanggal Lahir', 'Status', 'Nomor Induk Yayasan (NIY)', 'Jabatan'],
        ]);

        $splitPayload = $mapRow->invoke($service, [
            'Guru Format Terpisah', 'P', 'Demak', '1 Januari 1990', 'GTY', 'NIY-002', 'Guru Kelas',
        ], $splitColumns);

        $this->assertSame('Demak', $splitPayload['birth_place']);
        $this->assertSame('1990-01-01', $splitPayload['birth_date']);
    }

    public function test_employee_import_does_not_require_birth_columns(): void
    {
        $service = new EmployeeImportService();
        $detectColumns = new ReflectionMethod($service, 'detectColumns');
        $detectColumns->setAccessible(true);
        $mapRow = new ReflectionMethod($service, 'mapRow');
        $mapRow->setAccessible(true);

        [, $columns] = $detectColumns->invoke($service, [
            1 => ['Nama Lengkap', 'L/P', 'Status', 'Nomor Induk Yayasan (NIY)', 'Jabatan'],
        ]);

        $payload = $mapRow->invoke($service, [
            'Guru Tanpa Data Lahir', 'L', 'GTY', 'NIY-003', 'Guru Kelas',
        ], $columns);

        $this->assertNull($payload['birth_place']);
        $this->assertNull($payload['birth_date']);
    }


    public function test_employee_import_reads_data_personalia_when_it_is_not_the_first_worksheet(): void
    {
        $service = new EmployeeImportService();
        $template = new EmployeeImportTemplateService();
        $path = tempnam(sys_get_temp_dir(), 'employee-import-multisheet-');
        file_put_contents($path, $template->content());

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $dataSheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Sheet Pembuka</t></is></c></row></sheetData></worksheet>');
        $zip->addFromString('xl/worksheets/sheet2.xml', $dataSheet);
        $zip->close();

        $readRows = new ReflectionMethod($service, 'readRows');
        $readRows->setAccessible(true);
        $rows = $readRows->invoke($service, $path);

        @unlink($path);

        $this->assertSame('NAMA LENGKAP', $rows[1][1]);
        $this->assertSame('USWATUN KHASANAH, S.Pd.I., M.Pd.', $rows[2][1]);
    }


    public function test_employee_import_reads_headers_from_rich_text_shared_strings(): void
    {
        $service = new EmployeeImportService();
        $path = tempnam(sys_get_temp_dir(), 'employee-import-rich-strings-');

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::OVERWRITE) === true);
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><r><t>NAMA </t></r><r><t>LENGKAP</t></r></si><si><t>L/P</t></si><si><t>STATUS</t></si><si><t>NOMOR INDUK YAYASAN (NIY)</t></si><si><t>JABATAN</t></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c><c r="E1" t="s"><v>4</v></c></row></sheetData></worksheet>');
        $zip->close();

        $readRows = new ReflectionMethod($service, 'readRows');
        $readRows->setAccessible(true);
        $rows = $readRows->invoke($service, $path);

        @unlink($path);

        $this->assertSame('NAMA LENGKAP', $rows[1][0]);
    }

    public function test_employee_import_maps_principal_only_from_exact_school_head_position(): void
    {
        $service = new EmployeeImportService();
        $detectColumns = new ReflectionMethod($service, 'detectColumns');
        $detectColumns->setAccessible(true);
        $mapRow = new ReflectionMethod($service, 'mapRow');
        $mapRow->setAccessible(true);

        [, $columns] = $detectColumns->invoke($service, [
            10 => ['Nama Lengkap', 'L/P', 'Status', 'Nomor Induk Yayasan (NIY)', 'Peg.ID', 'Jabatan'],
        ]);

        $principalPayload = $mapRow->invoke($service, [
            'USWATUN KHASANAH, S.Pd.I., M.Pd.', 'P', 'GTY', '620.0720.001', '20367380193001', 'KEPALA MADRASAH',
        ], $columns);
        $classTeacherPayload = $mapRow->invoke($service, [
            'RO’IS RO’DATUL URBAH, S.Pd.', 'P', 'GTY', '620.0723.022', '20367380197004', 'GURU KELAS 2',
        ], $columns);
        $libraryHeadPayload = $mapRow->invoke($service, [
            'DEWI SHOFIYAH, S.Pd.', 'P', 'GTY', '620.0124.029', '20367380197005', 'KEPALA PERPUSTAKAAN',
        ], $columns);

        $this->assertSame(EmploymentType::Principal->value, $principalPayload['employment_type']);
        $this->assertSame(EmploymentType::ClassTeacher->value, $classTeacherPayload['employment_type']);
        $this->assertSame(EmploymentType::SubjectTeacher->value, $libraryHeadPayload['employment_type']);
        $this->assertSame('KEPALA MADRASAH', $principalPayload['position']);
        $this->assertSame('GURU KELAS 2', $classTeacherPayload['position']);
    }


    public function test_employee_import_matches_existing_employee_by_normalized_identifiers(): void
    {
        $existing = Employee::create($this->payload([
            'name' => 'RO’IS RO’DATUL URBAH, S.Pd.',
            'employee_number' => '6200723022',
            'peg_id' => '20367380197004',
            'position' => 'Kepala Madrasah',
            'employment_type' => EmploymentType::Principal->value,
        ]));

        $service = new EmployeeImportService();
        $findEmployee = new ReflectionMethod($service, 'findEmployee');
        $findEmployee->setAccessible(true);

        $matched = $findEmployee->invoke($service, [
            'name' => 'RO’IS RO’DATUL URBAH, S.Pd.',
            'employee_number' => '620.0723.022',
            'peg_id' => '20367380197004',
            'email' => null,
        ]);

        $this->assertTrue($existing->is($matched));
    }

    public function test_validation_rejects_duplicate_numbers_and_invalid_dates(): void
    {
        $admin = $this->userWith(['employees.create']);
        Employee::create($this->payload(['nip'=>'DUP','nuptk'=>'DUPN','employee_number'=>'DUPE','national_identity_number'=>'DUPK']));
        $this->actingAs($admin)->post(route('employees.store'), $this->payload(['nip'=>'DUP','nuptk'=>'DUPN','employee_number'=>'DUPE','national_identity_number'=>'DUPK','birth_date'=>now()->addDay()->toDateString(),'joined_at'=>'2026-02-01','left_at'=>'2026-01-01']))->assertSessionHasErrors(['nip','nuptk','employee_number','national_identity_number','birth_date','joined_at']);
    }

    public function test_activation_document_and_account_flows_are_authorized_and_logged(): void
    {
        Storage::fake('local');
        $admin = $this->userWith(['employees.view','employees.update','employees.activate','employees.manage-documents','employees.link-account']);
        $employee = Employee::create($this->payload());
        $this->actingAs($admin)->patch(route('employees.deactivate',$employee))->assertSessionHas('status');
        $this->assertFalse($employee->refresh()->is_active);
        $this->patch(route('employees.activate',$employee))->assertSessionHas('status');
        $this->assertTrue($employee->refresh()->is_active);
        $this->post(route('employees.documents.store',$employee), ['type'=>'ktp','file'=>UploadedFile::fake()->create('ktp.pdf',20,'application/pdf')])->assertSessionHas('status');
        $document = $employee->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
        $this->get(route('employee-documents.download',$document))->assertOk();
        $this->delete(route('employee-documents.destroy',$document))->assertSessionHas('status');
        Storage::disk('local')->assertMissing($document->file_path);
        Role::create(['name'=>'operator']);
        $this->post(route('employees.create-account',$employee), ['email'=>'pegawai@example.test','role'=>'operator'])->assertSessionHas('status');
        $user = $employee->refresh()->user;
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('not-the-password', $user->password) === false);
        $this->assertDatabaseHas('activity_log', ['event' => 'employee.account-created']);
        $existing = User::factory()->create(['email' => 'existing@example.test']);
        $otherEmployee = Employee::create($this->payload(['employee_number' => 'NIY2', 'email' => 'existing@example.test']));
        $this->post(route('employees.create-account', $otherEmployee), ['email' => 'existing@example.test', 'role' => 'operator'])->assertSessionHas('status');
        $this->assertSame($existing->id, $otherEmployee->refresh()->user_id);
        $this->assertDatabaseHas('activity_log', ['event' => 'employee.account-linked']);
    }

    public function test_view_own_cannot_open_other_employee(): void
    {
        $user = $this->userWith(['employees.view-own']);
        $own = Employee::create($this->payload(['user_id'=>$user->id, 'employee_number'=>'OWN']));
        $other = Employee::create($this->payload(['employee_number'=>'OTHER']));
        $this->actingAs($user)->get(route('employees.show',$own))->assertOk();
        $this->get(route('employees.show',$other))->assertForbidden();
        $this->get(route('employees.index'))->assertForbidden();
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active'=>true]);
        foreach ($permissions as $permission) Permission::findOrCreate($permission);
        $user->givePermissionTo($permissions);
        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + ['name'=>'Guru Tes','gender'=>Gender::Male->value,'employment_type'=>EmploymentType::ClassTeacher->value,'employee_status'=>EmployeeStatus::Honorary->value,'position'=>'Guru Kelas','birth_date'=>'1990-01-01','joined_at'=>'2020-01-01','is_active'=>true,'whatsapp'=>'08123456789','email'=>null];
    }
}
