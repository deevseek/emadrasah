<?php

declare(strict_types=1);

namespace App\Http\Requests\ReportCard;

use Illuminate\Foundation\Http\FormRequest;

final class ReportCardReopenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('report-cards.reopen') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['reason' => 'alasan buka ulang'];
    }
}
