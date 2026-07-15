<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('gradeLevel')?->id;

        return ['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50', Rule::unique('grade_levels', 'code')->ignore($id)], 'level' => ['required', 'integer', 'between:1,6', Rule::unique('grade_levels', 'level')->ignore($id)], 'description' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']];
    }
}
