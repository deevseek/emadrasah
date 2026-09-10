<?php

declare(strict_types=1);

namespace App\Http\Requests\Rfid;

use Illuminate\Foundation\Http\FormRequest;

class IssueRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->boolean('replace') ? 'rfid-card.replace' : 'rfid-card.issue';

        return $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return ['replace' => ['sometimes', 'boolean']];
    }
}
