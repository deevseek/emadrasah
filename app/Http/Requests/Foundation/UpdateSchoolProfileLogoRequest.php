<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolProfileLogoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048']]; }
    public function messages(): array { return ['logo.image' => 'Logo harus berupa gambar PNG, JPG, JPEG, atau WEBP.', 'logo.mimes' => 'Logo harus berupa gambar PNG, JPG, JPEG, atau WEBP.', 'logo.max' => 'Ukuran logo maksimal 2 MB.']; }
}
