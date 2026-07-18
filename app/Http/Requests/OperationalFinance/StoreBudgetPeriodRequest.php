<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetPeriodRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255'], 'fiscal_year' => ['required', 'integer', 'between:2000,2100'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'total_budget' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
