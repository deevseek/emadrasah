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
        $rule = ['required', 'image', 'mimes:jpeg', 'max:2048'];
        return ['front_1' => $rule, 'front_2' => $rule, 'natural' => $rule, 'left' => $rule, 'right' => $rule];
    }
}
