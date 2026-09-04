<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\GuardianRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => str((string) $this->input('name'))->squish()->toString(),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone_number' => preg_replace('/\s+/', '', (string) $this->input('phone_number')),
            'nisn' => preg_replace('/\D+/', '', (string) $this->input('nisn')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone_number' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'relationship' => ['required', Rule::enum(GuardianRelationship::class)],
            'nisn' => ['required', 'digits_between:8,15'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah digunakan. Silakan masuk atau gunakan fitur lupa password.',
            'phone_number.regex' => 'Nomor telepon harus terdiri dari 9–15 digit dan boleh diawali tanda +.',
            'nisn.digits_between' => 'NISN harus terdiri dari 8–15 digit.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'terms.accepted' => 'Persetujuan penggunaan data wajib dicentang.',
        ];
    }
}
