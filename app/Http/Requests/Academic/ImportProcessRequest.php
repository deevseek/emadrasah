<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class ImportProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('teaching-assignments.*')
            ? 'teaching-assignments.import'
            : 'schedules.import';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'preview_token' => ['required', 'uuid', 'string'],
        ];

        if ($this->routeIs('teaching-assignments.*')) {
            $rules['confirm_replace'] = ['sometimes', 'accepted'];
        }

        return $rules;
    }
}
