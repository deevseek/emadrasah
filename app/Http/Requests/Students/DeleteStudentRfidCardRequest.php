<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class DeleteStudentRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rfid-card.disable') === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [];
    }
}
