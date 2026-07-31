<?php

declare(strict_types=1);

namespace App\Http\Requests\Subjects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSubjectRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can($this->route('subject') ? 'subjects.update' : 'subjects.create'); }
    protected function prepareForValidation(): void { $this->merge(['code' => strtoupper(trim((string) $this->code)), 'name' => trim((string) $this->name), 'is_active' => $this->boolean('is_active')]); }
    public function rules(): array
    {
        return ['code' => ['required', 'max:30', Rule::unique('subjects')->ignore($this->route('subject'))], 'name' => ['required', 'max:150'], 'category' => ['nullable', 'max:80'], 'sort_order' => ['required', 'integer', 'min:0'], 'is_active' => ['boolean']];
    }
}
