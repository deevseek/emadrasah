<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class OperationalFinanceFilterRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'], 'finance_category_id' => ['nullable', 'integer', 'exists:finance_categories,id'], 'status' => ['nullable', 'string', 'max:50'], 'transaction_type' => ['nullable', 'string', 'in:income,expense,transfer_in,transfer_out'], 'search' => ['nullable', 'string', 'max:100']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
