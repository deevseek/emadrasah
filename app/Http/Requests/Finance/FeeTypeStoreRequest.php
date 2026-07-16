<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\Finance\BillingFrequency;
use App\Enums\Finance\FeeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FeeTypeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fee-types.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_mandatory' => $this->boolean('is_mandatory'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:fee_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(FeeCategory::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'billing_frequency' => ['required', Rule::enum(BillingFrequency::class)],
            'is_mandatory' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'revenue_account_id' => ['nullable', 'integer', 'exists:chart_accounts,id'],
        ];
    }
}
