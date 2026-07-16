<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

final class UpdateBtaqJournalRequest extends StoreBtaqJournalRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('btaq-journals.update') ?? false;
    }
}
