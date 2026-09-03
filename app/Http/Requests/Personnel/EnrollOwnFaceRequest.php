<?php

declare(strict_types=1);

namespace App\Http\Requests\Personnel;

use Illuminate\Foundation\Http\FormRequest;

class EnrollOwnFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->personnel()->where('is_active', true)->exists() === true;
    }

    public function rules(): array
    {
        return [
            'front' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'left' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'right' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
        ];
    }
}
