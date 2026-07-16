<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PayrollPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payrolls.mark-paid') ?? false;
    }

    public function rules(): array
    {
        return [
            'cash_account_id' => [
                'required',
                'integer',
                Rule::exists('cash_accounts', 'id')->where('is_active', true),
            ],
        ];
    }
}
