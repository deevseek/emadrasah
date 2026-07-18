<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashClosingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['cash_account_id' => ['required', 'exists:cash_accounts,id'], 'closing_date' => ['required', 'date'], 'actual_balance' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
