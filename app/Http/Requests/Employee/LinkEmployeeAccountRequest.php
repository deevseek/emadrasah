<?php

declare(strict_types=1);
namespace App\Http\Requests\Employee;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class LinkEmployeeAccountRequest extends FormRequest
{ public function authorize(): bool { return $this->user()?->can('employees.link-account') ?? false; } public function rules(): array { return ['user_id'=>['required',Rule::exists('users','id')->where(fn($q)=>$q->whereNotIn('id', fn($s)=>$s->select('user_id')->from('employees')->whereNotNull('user_id')))] ]; } }
