<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ParentLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nisn' => preg_replace('/\D+/', '', (string) $this->input('nisn')),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'nisn' => ['required', 'digits_between:8,15'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.required' => 'NISN anak wajib diisi.',
            'nisn.digits_between' => 'NISN anak harus terdiri dari 8–15 digit.',
        ];
    }
}
