<?php

declare(strict_types=1);
namespace App\Http\Requests\Employee;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class CreateEmployeeAccountRequest extends FormRequest
{ public function authorize(): bool { return $this->user()?->can('employees.link-account') ?? false; } public function rules(): array { return ['email'=>['required','email','max:255','unique:users,email'], 'role'=>['required',Rule::exists('roles','name')]]; } }
