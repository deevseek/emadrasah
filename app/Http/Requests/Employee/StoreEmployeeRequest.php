<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employees.create') ?? false; }

    protected function prepareForValidation(): void
    {
        $this->merge(['employment_type' => $this->employmentTypeFromPosition((string) $this->input('position'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'employee_status' => ['required', Rule::enum(EmployeeStatus::class)],
            'employee_number' => ['nullable', 'string', 'max:100', 'unique:employees,employee_number'],
            'nip' => ['nullable', 'string', 'max:100', 'unique:employees,nip'],
            'rank_grade' => ['nullable', 'string', 'max:150'],
            'peg_id' => ['nullable', 'string', 'max:100'],
            'last_education' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:150'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'certification_status' => ['nullable', 'string', 'max:150'],
            'certification_subject' => ['nullable', 'string', 'max:150'],
            'weekly_teaching_hours' => ['nullable', 'integer', 'min:0', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:employees,email'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.unique' => 'Nomor induk yayasan (NIY) sudah digunakan oleh guru/pegawai lain.',
            'nip.unique' => 'NIP sudah digunakan oleh guru/pegawai lain.',
            'email.email' => 'E-mail aktif harus menggunakan format alamat email yang benar.',
            'email.unique' => 'E-mail aktif sudah digunakan oleh guru/pegawai lain. Gunakan e-mail lain atau periksa kembali data pegawai yang sudah ada.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'gender' => 'L/P',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'employee_status' => 'status',
            'employee_number' => 'nomor induk yayasan (NIY)',
            'nip' => 'NIP',
            'rank_grade' => 'pangkat/golongan ruang',
            'peg_id' => 'Peg.ID',
            'last_education' => 'pendidikan terakhir',
            'position' => 'jabatan',
            'certification_status' => 'sertifikasi-impassing',
            'certification_subject' => 'mapel sertifikasi',
            'weekly_teaching_hours' => 'jumlah JPL',
            'bank_name' => 'jenis rekening',
            'bank_account_number' => 'nomor rekening',
            'whatsapp' => 'nomor HP/WA aktif',
            'email' => 'e-mail aktif',
        ];
    }

    protected function employmentTypeFromPosition(string $position): string
    {
        $position = $this->normalizedPosition($position);

        return match (true) {
            in_array($position, ['kepala madrasah', 'kepala sekolah'], true) => EmploymentType::Principal->value,
            str_contains($position, 'guru kelas') => EmploymentType::ClassTeacher->value,
            str_contains($position, 'btaq') => EmploymentType::BtaqTeacher->value,
            str_contains($position, 'tata usaha') || $position === 'tu' => EmploymentType::Administration->value,
            str_contains($position, 'bersih') => EmploymentType::EducationStaff->value,
            default => EmploymentType::SubjectTeacher->value,
        };
    }

    private function normalizedPosition(string $position): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower($position)));
    }
}
