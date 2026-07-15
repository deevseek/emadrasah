<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;

class SchoolProfileRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('school-profile.update') ?? false; }
    public function rules(): array
    {
        return [
            'school_name' => ['required','string','max:255'], 'foundation_name' => ['nullable','string','max:255'],
            'npsn' => ['nullable','string','max:32'], 'nsm' => ['nullable','string','max:32'], 'address' => ['nullable','string'],
            'village' => ['nullable','string','max:255'], 'district' => ['nullable','string','max:255'], 'city' => ['nullable','string','max:255'],
            'province' => ['nullable','string','max:255'], 'postal_code' => ['nullable','string','max:12'], 'phone' => ['nullable','string','max:255'],
            'email' => ['nullable','email','max:255'], 'website' => ['nullable','url','max:255'], 'principal_name' => ['nullable','string','max:255'],
            'timezone' => ['required','timezone'], 'logo' => ['nullable','image','max:2048'], 'principal_signature' => ['nullable','image','max:2048'], 'stamp' => ['nullable','image','max:2048'],
        ];
    }
    public function messages(): array { return ['school_name.required' => 'Nama madrasah wajib diisi.', 'logo.image' => 'Logo harus berupa gambar.']; }
}
