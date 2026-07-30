<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $values = collect($this->except(['_token', '_method']))->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
        if (isset($values['email'])) $values['email'] = strtolower($values['email']);
        $this->merge($values);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'], 'short_name' => ['nullable', 'string', 'max:100'],
            'education_level' => ['required', 'string', 'max:100'], 'status' => ['nullable', 'in:Negeri,Swasta'],
            'nsm' => ['nullable', 'digits_between:1,20'], 'npsn' => ['nullable', 'digits_between:1,20'],
            'address' => ['nullable', 'string', 'max:1000'], 'village' => ['nullable', 'string', 'max:150'],
            'district' => ['nullable', 'string', 'max:150'], 'city' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'], 'postal_code' => ['nullable', 'digits_between:1,10'],
            'phone' => ['nullable', 'string', 'max:30'], 'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'], 'website' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Nama madrasah wajib diisi.', 'nsm.digits_between' => 'NSM hanya boleh berisi angka.', 'npsn.digits_between' => 'NPSN hanya boleh berisi angka.', 'email.email' => 'Format email tidak valid.', 'website.url' => 'Format alamat website tidak valid.'];
    }
}
