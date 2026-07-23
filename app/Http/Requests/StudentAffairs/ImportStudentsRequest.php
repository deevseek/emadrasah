<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('students.create') ?? false;
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:xlsx', 'max:5120']];
    }

    public function attributes(): array
    {
        return ['file' => 'berkas XLSX daftar siswa'];
    }
}
