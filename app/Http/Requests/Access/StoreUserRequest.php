<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    protected function prepareForValidation(): void { $this->merge(['username' => strtolower(trim((string) $this->username)), 'email' => strtolower(trim((string) $this->email))]); }
    public function authorize(): bool { return $this->user()?->can('users.create') === true; }
    public function rules(): array { return ['name' => ['required','string','max:150'], 'username' => ['required','string','min:4','max:50','regex:/^[a-z0-9._-]+$/','unique:users,username'], 'email' => ['required','email','max:255','unique:users,email'], 'role' => ['required', Rule::exists('roles','name')->where('guard_name','web')], 'password' => ['required','string','min:8','confirmed'], 'is_active' => ['required','boolean']]; }
    public function withValidator($validator): void { $validator->after(function ($validator): void { if ($this->role === 'super-admin' && ! $this->user()?->hasRole('super-admin')) $validator->errors()->add('role', 'Role yang dipilih tidak tersedia.'); }); }
    public function messages(): array { return ['username.unique'=>'Username sudah digunakan.','username.regex'=>'Username hanya boleh berisi huruf kecil, angka, titik, garis bawah, dan tanda minus.','email.unique'=>'Email sudah digunakan.','password.min'=>'Password minimal terdiri dari 8 karakter.','password.confirmed'=>'Konfirmasi password tidak sesuai.','role.required'=>'Role wajib dipilih.']; }
}
