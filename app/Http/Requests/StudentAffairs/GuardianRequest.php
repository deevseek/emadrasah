<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;

use App\Enums\Gender; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class GuardianRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('guardian')?->id; return ['user_id'=>['nullable','exists:users,id',Rule::unique('guardians','user_id')->ignore($id)],'national_identity_number'=>['nullable','string','max:50',Rule::unique('guardians')->ignore($id)],'family_card_number'=>['nullable','string','max:50'],'name'=>['required','string','max:255'],'gender'=>['nullable',Rule::enum(Gender::class)],'birth_place'=>['nullable','string','max:100'],'birth_date'=>['nullable','date'],'education'=>['nullable','string','max:100'],'occupation'=>['nullable','string','max:100'],'monthly_income'=>['nullable','numeric','min:0'],'phone'=>['nullable','string','max:50'],'email'=>['nullable','email','max:255'],'address'=>['nullable','string'],'is_active'=>['nullable','boolean']]; } }
