<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    protected function prepareForValidation(): void { $this->merge(['username'=>strtolower(trim((string)$this->username)),'email'=>strtolower(trim((string)$this->email))]); }
    public function authorize(): bool { return $this->user()?->can('users.update') === true; }
    public function rules(): array { $target=$this->route('user'); return ['name'=>['required','string','max:150'],'username'=>['required','min:4','max:50',new Username,Rule::unique('users')->ignore($target)],'email'=>['required','email',Rule::unique('users')->ignore($target)],'roles'=>['required','array','min:1'],'roles.*'=>['string','distinct',Rule::exists('roles','name')->where('guard_name','web')],'is_active'=>['required','boolean']]; }
    public function withValidator($validator): void { $validator->after(function($validator): void { $target=$this->route('user'); $roles=$this->input('roles',[]); $changed=collect($roles)->sort()->values()->all()!==$target->roles->pluck('name')->sort()->values()->all(); if($changed && (! $this->user()?->can('users.assign-role') || ! $this->user()?->hasAnyRole(['operator','super-admin','kepala-madrasah'])))$validator->errors()->add('roles','Anda tidak memiliki hak untuk menentukan role pengguna.'); if(in_array('super-admin',$roles,true)&&!$this->user()?->hasRole('super-admin'))$validator->errors()->add('roles','Role yang dipilih tidak tersedia.'); if((bool)$this->boolean('is_active') !== (bool)$target->is_active && ! $this->user()?->can('users.activate'))$validator->errors()->add('is_active','Anda tidak memiliki hak untuk mengubah status pengguna.'); }); }
    public function messages(): array { return ['username.unique'=>'Username sudah digunakan.','email.unique'=>'Email sudah digunakan.','roles.required'=>'Minimal satu role wajib dipilih.','roles.min'=>'Minimal satu role wajib dipilih.']; }
}
