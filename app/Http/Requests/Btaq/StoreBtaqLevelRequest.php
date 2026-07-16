<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBtaqLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('btaq-levels.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('btaq_levels', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'sequence' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function attributes(): array
    {
        return ['code' => 'kode', 'name' => 'nama level', 'sequence' => 'urutan', 'is_active' => 'status aktif'];
    }
}
