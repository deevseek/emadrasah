<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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
