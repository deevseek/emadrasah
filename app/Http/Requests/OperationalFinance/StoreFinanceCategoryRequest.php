<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinanceCategoryRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['code' => ['required', 'string', 'max:50', 'unique:finance_categories,code'], 'name' => ['required', 'string', 'max:255'], 'transaction_type' => ['required', 'in:income,expense'], 'parent_id' => ['nullable', 'exists:finance_categories,id'], 'description' => ['nullable', 'string', 'max:1000'], 'is_budgetable' => ['sometimes', 'boolean'], 'requires_approval' => ['sometimes', 'boolean'], 'approval_threshold' => ['nullable', 'numeric', 'min:0'], 'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
