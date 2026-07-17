<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Enums\SubjectCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can($this->route('subject') ? 'subjects.update' : 'subjects.create') ?? false; }
    protected function prepareForValidation(): void { $this->merge(['code' => strtoupper(str($this->input('code'))->trim()->replace(' ', '-')->toString())]); }
    public function rules(): array
    {
        $id = $this->route('subject')?->id;
        return [
            'code' => ['required','string','max:50', Rule::unique('subjects','code')->ignore($id)],
            'name' => ['required','string','max:255'], 'short_name' => ['nullable','string','max:50'],
            'category' => ['required', Rule::enum(SubjectCategory::class)], 'grade_level_ids' => ['array'], 'grade_level_ids.*' => ['exists:grade_levels,id'],
            'default_weekly_hours' => ['nullable','integer','min:1','max:60'], 'sort_order' => ['nullable','integer','min:0','max:1000'],
            'description' => ['nullable','string'], 'minimum_passing_grade' => ['nullable','integer','between:0,100'], 'is_active' => ['nullable','boolean'],
        ];
    }
}
