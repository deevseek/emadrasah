<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.create') ?? false;
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:xlsx', 'max:5120']];
    }

    public function attributes(): array
    {
        return ['file' => 'berkas XLSX data personalia'];
    }
}
