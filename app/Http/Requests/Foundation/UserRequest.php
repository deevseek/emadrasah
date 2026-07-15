<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission($this->route('user') ? 'users.update' : 'users.create') ?? false; }
    public function rules(): array
    {
        $id = $this->route('user')?->id;
        return ['name'=>['required','string','max:255'], 'email'=>['required','email',Rule::unique('users')->ignore($id)], 'password'=>[$id ? 'nullable' : 'required','string','min:8'], 'roles'=>['array'], 'roles.*'=>['exists:roles,id']];
    }
}
