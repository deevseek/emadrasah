<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.delete') === true;
    }

    public function rules(): array
    {
        return [];
    }
}
