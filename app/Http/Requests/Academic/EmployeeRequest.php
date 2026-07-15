<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;
use App\Enums\EmployeeStatus; use App\Enums\EmploymentType; use App\Enums\Gender; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class EmployeeRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('employee')?->id; return ['user_id'=>['nullable','exists:users,id',Rule::unique('employees','user_id')->ignore($id)],'employee_number'=>['nullable','string','max:100',Rule::unique('employees','employee_number')->ignore($id)],'national_identity_number'=>['nullable','string','max:100',Rule::unique('employees','national_identity_number')->ignore($id)],'name'=>['required','string','max:255'],'gender'=>['required',Rule::enum(Gender::class)],'birth_place'=>['nullable','string','max:255'],'birth_date'=>['nullable','date'],'address'=>['nullable','string'],'phone'=>['nullable','string','max:50'],'email'=>['nullable','email','max:255'],'employment_type'=>['required',Rule::enum(EmploymentType::class)],'employee_status'=>['required',Rule::enum(EmployeeStatus::class)],'joined_at'=>['nullable','date'],'left_at'=>['nullable','date','after_or_equal:joined_at'],'photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],'is_active'=>['nullable','boolean']]; } }
