<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

use App\Enums\BtaqMaterialCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBtaqMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('btaq-materials.manage') ?? false;
    }
    public function rules(): array
    {
        return [
            'btaq_level_id' => ['required', Rule::exists('btaq_levels', 'id')->where('is_active', true)],
            'code' => ['required', 'string', 'max:50', Rule::unique('btaq_materials', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(BtaqMaterialCategory::class)],
            'sequence' => ['required', 'integer', 'min:1'],
            'target_description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
    public function attributes(): array
    {
        return [
            'btaq_level_id' => 'level BTAQ',
            'code' => 'kode',
            'name' => 'nama materi',
            'category' => 'kategori',
            'sequence' => 'urutan',
        ];
    }
}
