<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\NormalBalance;
use App\Models\Finance\ChartAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChartAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance-accounts.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
            'is_cash_account' => $this->boolean('is_cash_account'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $chartAccount = $this->route('chartAccount');
        $chartAccountId = $chartAccount instanceof ChartAccount
            ? $chartAccount->getKey()
            : $chartAccount;

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:chart_accounts,id',
                Rule::notIn(array_filter([$chartAccountId])),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chart_accounts', 'code')->ignore($chartAccountId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', Rule::enum(AccountType::class)],
            'normal_balance' => ['required', Rule::enum(NormalBalance::class)],
            'is_cash_account' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sequence' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
