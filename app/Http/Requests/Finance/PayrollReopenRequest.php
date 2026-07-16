<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class PayrollReopenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payrolls.reopen') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
