<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperationalExpenseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['transaction_date' => ['required', 'date'], 'cash_account_id' => ['required', 'exists:cash_accounts,id'], 'finance_category_id' => ['required', 'exists:finance_categories,id'], 'amount' => ['required', 'numeric', 'min:1'], 'description' => ['required', 'string', 'max:1000'], 'reference_number' => ['nullable', 'string', 'max:100'], 'budget_allocation_id' => ['nullable', 'exists:budget_allocations,id'], 'notes' => ['nullable', 'string', 'max:1000'], 'post_now' => ['sometimes', 'boolean']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
