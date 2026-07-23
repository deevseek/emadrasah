<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.link-account') ?? false;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Employee::query()->whereHas('user', fn ($query) => $query->where('email', $value))->exists()) {
                        $fail('Email ini sudah terhubung dengan data pegawai lain.');
                    }
                },
            ],
            'role' => ['required', Rule::exists('roles', 'name')],
        ];
    }
}
