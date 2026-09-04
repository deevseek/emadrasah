<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    protected function prepareForValidation(): void { $this->merge(['username' => strtolower(trim((string) $this->username)), 'email' => strtolower(trim((string) $this->email))]); }
    public function authorize(): bool { return $this->user()?->can('users.create') === true; }
    public function rules(): array { return ['name' => ['required','string','max:150'], 'username' => ['required','string','min:4','max:50',new Username,'unique:users,username'], 'email' => ['required','email','max:255','unique:users,email'], 'roles' => ['required','array','min:1'], 'roles.*' => ['string','distinct', Rule::exists('roles','name')->where('guard_name','web')], 'password' => ['required','string','min:8','confirmed'], 'is_active' => ['required','boolean']]; }
    public function withValidator($validator): void { $validator->after(function ($validator): void { if (in_array('super-admin', $this->input('roles', []), true) && ! $this->user()?->hasRole('super-admin')) $validator->errors()->add('roles', 'Role yang dipilih tidak tersedia.'); }); }
    public function messages(): array { return ['username.unique'=>'Username sudah digunakan.','email.unique'=>'Email sudah digunakan.','password.min'=>'Password minimal terdiri dari 8 karakter.','password.confirmed'=>'Konfirmasi password tidak sesuai.','roles.required'=>'Minimal satu role wajib dipilih.','roles.min'=>'Minimal satu role wajib dipilih.']; }
}
