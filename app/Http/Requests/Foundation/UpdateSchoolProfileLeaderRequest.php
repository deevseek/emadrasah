<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolProfileLeaderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['head_name' => trim((string) $this->input('head_name')), 'head_nip' => trim((string) $this->input('head_nip'))]); }
    public function rules(): array { return ['head_name' => ['nullable', 'string', 'max:200'], 'head_nip' => ['nullable', 'digits_between:1,30']]; }
    public function messages(): array { return ['head_nip.digits_between' => 'NIP hanya boleh berisi angka.']; }
}
