<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashAccountRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255'], 'code' => ['nullable', 'string', 'max:50', 'unique:cash_accounts,code'], 'account_type' => ['required', 'string', 'in:cash,bank,petty_cash,other'], 'institution_name' => ['nullable', 'string', 'max:255'], 'account_number' => ['nullable', 'string', 'max:100'], 'account_holder' => ['nullable', 'string', 'max:255'], 'opening_balance' => ['required', 'numeric', 'min:0'], 'opening_balance_date' => ['nullable', 'date'], 'allow_negative_balance' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean'], 'is_default' => ['sometimes', 'boolean'], 'notes' => ['nullable', 'string', 'max:1000']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
