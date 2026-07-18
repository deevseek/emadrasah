<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashTransferRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['transfer_date' => ['required', 'date'], 'source_cash_account_id' => ['required', 'exists:cash_accounts,id'], 'destination_cash_account_id' => ['required', 'exists:cash_accounts,id', 'different:source_cash_account_id'], 'amount' => ['required', 'numeric', 'min:1'], 'description' => ['required', 'string', 'max:1000'], 'reference_number' => ['nullable', 'string', 'max:100']]; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian', 'cash_account_id' => 'akun kas', 'finance_category_id' => 'kategori']; }
}
