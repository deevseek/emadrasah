<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employees.create') ?? false; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'front_title' => ['nullable', 'string', 'max:50'], 'back_title' => ['nullable', 'string', 'max:50'],
            'employee_number' => ['nullable', 'string', 'max:100', 'unique:employees,employee_number'], 'nip' => ['nullable', 'string', 'max:100', 'unique:employees,nip'], 'nuptk' => ['nullable', 'string', 'max:100', 'unique:employees,nuptk'], 'national_identity_number' => ['nullable', 'string', 'max:100', 'unique:employees,national_identity_number'],
            'gender' => ['required', Rule::enum(Gender::class)], 'birth_place' => ['nullable', 'string', 'max:100'], 'birth_date' => ['nullable', 'date', 'before_or_equal:today'], 'religion' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'], 'whatsapp' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255', 'unique:employees,email'], 'address' => ['nullable', 'string'], 'village' => ['nullable', 'string', 'max:100'], 'district' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'postal_code' => ['nullable', 'string', 'max:20'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)], 'employee_status' => ['required', Rule::enum(EmployeeStatus::class)], 'position' => ['required', 'string', 'max:150'], 'joined_at' => ['nullable', 'date', 'before_or_equal:left_at'], 'left_at' => ['nullable', 'date', 'after_or_equal:joined_at'], 'is_active' => ['nullable', 'boolean'], 'notes' => ['nullable', 'string'],
            'last_education' => ['nullable', 'string', 'max:100'], 'major' => ['nullable', 'string', 'max:150'], 'education_institution' => ['nullable', 'string', 'max:150'], 'graduation_year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
    public function attributes(): array { return ['name'=>'nama lengkap','employee_number'=>'NIY/nomor pegawai yayasan','national_identity_number'=>'NIK','employment_type'=>'kategori pegawai','employee_status'=>'status kepegawaian','position'=>'jabatan di madrasah','joined_at'=>'tanggal mulai bekerja','left_at'=>'tanggal selesai bekerja','photo'=>'foto pegawai']; }
}
