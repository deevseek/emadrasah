<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetAllocationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['budget_period_id' => ['required', 'exists:budget_periods,id'], 'finance_category_id' => ['required', 'exists:finance_categories,id'], 'allocated_amount' => ['required', 'numeric', 'min:0'], 'revised_amount' => ['nullable', 'numeric', 'min:0'], 'revision_reason' => ['nullable', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:1000']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
