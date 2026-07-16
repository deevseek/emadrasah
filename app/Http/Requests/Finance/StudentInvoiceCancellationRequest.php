<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class StudentInvoiceCancellationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('student-invoices.cancel') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
