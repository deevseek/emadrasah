<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Enums\SubjectCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('subject')?->id;

        return ['code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($id)], 'name' => ['required', 'string', 'max:255'], 'category' => ['required', Rule::enum(SubjectCategory::class)], 'description' => ['nullable', 'string'], 'minimum_passing_grade' => ['nullable', 'integer', 'between:0,100'], 'is_active' => ['nullable', 'boolean']];
    }
}
