<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

use Illuminate\Foundation\Http\FormRequest;

final class BtaqJournalRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('btaq-journals.reject') ?? false;
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['rejection_reason' => 'alasan penolakan'];
    }
}
