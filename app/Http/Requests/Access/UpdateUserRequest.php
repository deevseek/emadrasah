<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    protected function prepareForValidation(): void { $this->merge(['username'=>strtolower(trim((string)$this->username)),'email'=>strtolower(trim((string)$this->email))]); }
    public function authorize(): bool { return $this->user()?->can('users.update') === true; }
    public function rules(): array { $target=$this->route('user'); return ['name'=>['required','string','max:150'],'username'=>['required','min:4','max:50','regex:/^[a-z0-9._-]+$/',Rule::unique('users')->ignore($target)],'email'=>['required','email',Rule::unique('users')->ignore($target)],'role'=>['required',Rule::exists('roles','name')->where('guard_name','web')],'is_active'=>['required','boolean']]; }
    public function withValidator($validator): void { $validator->after(function($validator): void { $target=$this->route('user'); if($this->role!==$target->roles->first()?->name && ! $this->user()?->can('users.assign-role'))$validator->errors()->add('role','Anda tidak memiliki hak untuk menentukan role pengguna.'); if($this->role==='super-admin'&&!$this->user()?->hasRole('super-admin'))$validator->errors()->add('role','Role yang dipilih tidak tersedia.'); }); }
    public function messages(): array { return ['username.unique'=>'Username sudah digunakan.','email.unique'=>'Email sudah digunakan.','role.required'=>'Role wajib dipilih.']; }
}
