<?php

declare(strict_types=1);

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeService
{
    private const EMPLOYEE_FIELDS = ['user_id','front_title','employee_number','nip','nuptk','national_identity_number','name','back_title','gender','birth_place','birth_date','religion','address','village','district','city','province','postal_code','phone','whatsapp','email','employment_type','employee_status','position','rank_grade','peg_id','certification_status','certification_subject','weekly_teaching_hours','bank_name','bank_account_number','joined_at','left_at','notes','last_education','major','education_institution','graduation_year','is_active'];

    public function create(array $data, ?UploadedFile $photo): Employee
    {
        $newPhoto = $this->storePhoto($photo);
        try { return DB::transaction(function () use ($data, $newPhoto) { $employee = Employee::create($this->payload($data) + ['photo_path'=>$newPhoto]); $this->log('employee.created', $employee, ['nama'=>$employee->fullName()]); return $employee; }); }
        catch (\Throwable $e) { if ($newPhoto) Storage::disk('local')->delete($newPhoto); throw $e; }
    }

    public function update(Employee $employee, array $data, ?UploadedFile $photo): Employee
    {
        $newPhoto = $this->storePhoto($photo); $oldPhoto = $employee->photo_path; $before = Arr::only($employee->getAttributes(), ['nip','nuptk','employee_number','position','employee_status','user_id','is_active']);
        try { DB::transaction(function () use ($employee, $data, $newPhoto) { $payload = $this->payload($data); if ($newPhoto) $payload['photo_path'] = $newPhoto; $employee->update($payload); $this->log('employee.updated', $employee, ['perubahan'=>$employee->getChanges()]); }); }
        catch (\Throwable $e) { if ($newPhoto) Storage::disk('local')->delete($newPhoto); throw $e; }
        if ($newPhoto && $oldPhoto) Storage::disk('local')->delete($oldPhoto);
        return $employee->refresh();
    }

    public function activate(Employee $employee, bool $active): void
    { DB::transaction(function () use ($employee, $active) { $employee->update(['is_active'=>$active, 'left_at'=>$active ? null : ($employee->left_at ?: now()->toDateString())]); $this->log($active ? 'employee.activated' : 'employee.deactivated', $employee, ['status'=>$active ? 'Aktif' : 'Tidak Aktif']); }); }

    public function storeDocument(Employee $employee, array $data, UploadedFile $file, ?EmployeeDocument $document = null): EmployeeDocument
    {
        $path = $this->storeDocumentFile($file); $old = $document?->file_path;
        try { $saved = DB::transaction(function () use ($employee, $data, $file, $path, $document) { $payload = Arr::only($data, ['type','document_number','document_date','description']) + ['file_path'=>$path,'file_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType() ?: 'application/octet-stream','file_size'=>$file->getSize(),'uploaded_by'=>auth()->id()]; if ($document) { $document->update($payload); $saved=$document; } else { $saved=$employee->documents()->create($payload); } $this->log('employee.document-saved', $employee, ['dokumen'=>$payload['type']]); return $saved; }); }
        catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        if ($old && $old !== $path) Storage::disk('local')->delete($old); return $saved;
    }

    public function deleteDocument(EmployeeDocument $document): void
    { DB::transaction(function () use ($document) { $employee=$document->employee; $path=$document->file_path; $document->delete(); $this->log('employee.document-deleted', $employee, ['dokumen'=>$document->type->label()]); Storage::disk('local')->delete($path); }); }

    public function linkAccount(Employee $employee, User $user): void
    { DB::transaction(function () use ($employee, $user) { abort_if(! $employee->is_active, 422, 'Aktifkan pegawai sebelum menghubungkan akun.'); abort_if(Employee::where('user_id',$user->id)->whereKeyNot($employee->id)->exists(), 422, 'Akun sudah terhubung dengan pegawai lain.'); $old=$employee->user_id; $employee->update(['user_id'=>$user->id]); $this->log('employee.account-linked', $employee, ['akun_lama'=>$old,'akun_baru'=>$user->id]); }); }

    public function createAccount(Employee $employee, string $email, string $role): User
    { return DB::transaction(function () use ($employee, $email, $role) { abort_if(! $employee->is_active, 422, 'Aktifkan pegawai sebelum membuat akun.'); abort_if($employee->user_id, 422, 'Pegawai sudah memiliki akun.'); $user=User::where('email', $email)->first(); $event='employee.account-linked'; if (! $user) { $user=User::create(['name'=>$employee->fullName(),'email'=>$email,'password'=>Hash::make(Str::password(16)),'is_active'=>true]); $event='employee.account-created'; } abort_if(Employee::where('user_id',$user->id)->whereKeyNot($employee->id)->exists(), 422, 'Akun sudah terhubung dengan pegawai lain.'); $user->assignRole($role); $employee->update(['user_id'=>$user->id,'email'=>$employee->email ?: $email]); $this->log($event, $employee, ['akun_baru'=>$user->id,'role'=>$role]); return $user; }); }

    private function payload(array $data): array { $data['whatsapp'] = $this->normalizePhone($data['whatsapp'] ?? null); return Arr::only($data, self::EMPLOYEE_FIELDS); }
    private function storePhoto(?UploadedFile $photo): ?string { return $photo?->storeAs('employee-photos', Str::uuid().'.'.$photo->extension(), 'local'); }
    private function storeDocumentFile(UploadedFile $file): string { return $file->storeAs('employee-documents', Str::uuid().'.'.$file->extension(), 'local'); }
    private function normalizePhone(?string $value): ?string { if (! $value) return null; $n=preg_replace('/[^0-9+]/','',$value); return str_starts_with($n,'0') ? '+62'.substr($n,1) : $n; }
    private function log(string $event, Employee $employee, array $properties=[]): void { activity('employees')->event($event)->performedOn($employee)->causedBy(auth()->user())->withProperties($properties)->log($event); }
}
