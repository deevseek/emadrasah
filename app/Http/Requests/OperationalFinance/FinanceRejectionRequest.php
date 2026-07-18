<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class FinanceRejectionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
