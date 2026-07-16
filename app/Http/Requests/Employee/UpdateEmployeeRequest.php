<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeStatus; use App\Enums\EmploymentType; use App\Enums\Gender; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employees.update') ?? false; }
    public function rules(): array
    { $id = $this->route('employee')?->id; return [
        'name'=>['required','string','max:255'], 'front_title'=>['nullable','string','max:50'], 'back_title'=>['nullable','string','max:50'],
        'employee_number'=>['nullable','string','max:100',Rule::unique('employees','employee_number')->ignore($id)], 'nip'=>['nullable','string','max:100',Rule::unique('employees','nip')->ignore($id)], 'nuptk'=>['nullable','string','max:100',Rule::unique('employees','nuptk')->ignore($id)], 'national_identity_number'=>['nullable','string','max:100',Rule::unique('employees','national_identity_number')->ignore($id)],
        'gender'=>['required',Rule::enum(Gender::class)], 'birth_place'=>['nullable','string','max:100'], 'birth_date'=>['nullable','date','before_or_equal:today'], 'religion'=>['nullable','string','max:50'],
        'phone'=>['nullable','string','max:30'], 'whatsapp'=>['nullable','string','max:30'], 'email'=>['nullable','email','max:255',Rule::unique('employees','email')->ignore($id)], 'address'=>['nullable','string'], 'village'=>['nullable','string','max:100'], 'district'=>['nullable','string','max:100'], 'city'=>['nullable','string','max:100'], 'province'=>['nullable','string','max:100'], 'postal_code'=>['nullable','string','max:20'],
        'employment_type'=>['required',Rule::enum(EmploymentType::class)], 'employee_status'=>['required',Rule::enum(EmployeeStatus::class)], 'position'=>['required','string','max:150'], 'joined_at'=>['nullable','date','before_or_equal:left_at'], 'left_at'=>['nullable','date','after_or_equal:joined_at'], 'notes'=>['nullable','string'],
        'last_education'=>['nullable','string','max:100'], 'major'=>['nullable','string','max:150'], 'education_institution'=>['nullable','string','max:150'], 'graduation_year'=>['nullable','integer','min:1900','max:'.((int) date('Y') + 1)], 'photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
    ]; }
    public function attributes(): array { return (new StoreEmployeeRequest)->attributes(); }
}
