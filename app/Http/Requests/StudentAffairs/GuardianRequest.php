<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardianRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('guardian')?->id;
        return [
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('guardians', 'user_id')->ignore($id)],
            'national_identity_number' => ['nullable', 'string', 'max:50', Rule::unique('guardians')->ignore($id)],
            'family_card_number' => ['nullable', 'string', 'max:50'], 'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', Rule::enum(Gender::class)], 'birth_place' => ['nullable', 'string', 'max:100'], 'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'religion' => ['nullable', 'string', 'max:50'], 'relationship_type' => ['nullable', Rule::enum(GuardianRelationship::class)],
            'education' => ['nullable', 'string', 'max:100'], 'occupation' => ['nullable', 'string', 'max:100'], 'workplace' => ['nullable', 'string', 'max:150'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'], 'income_range' => ['nullable', 'string', 'max:100'], 'life_status' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'], 'whatsapp' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'], 'village' => ['nullable', 'string', 'max:100'], 'district' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'postal_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
