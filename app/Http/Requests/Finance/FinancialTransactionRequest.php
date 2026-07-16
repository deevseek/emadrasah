<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\Finance\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class FinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('post')
            ? 'finance-transactions.create'
            : 'finance-transactions.update';

        return $this->user()?->can($permission) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->map(static fn (array $line): array => [
                ...$line,
                'cash_account_id' => filled($line['cash_account_id'] ?? null)
                    ? $line['cash_account_id']
                    : null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
            ])
            ->all();

        $this->merge(['lines' => $lines]);
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', Rule::enum(TransactionType::class)],
            'description' => ['required', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_account_id' => ['required', 'integer', 'exists:chart_accounts,id'],
            'lines.*.cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $debitTotal = 0.0;
                $creditTotal = 0.0;

                foreach ($this->input('lines', []) as $index => $line) {
                    $debit = (float) ($line['debit'] ?? 0);
                    $credit = (float) ($line['credit'] ?? 0);

                    if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                        $validator->errors()->add(
                            "lines.{$index}.debit",
                            'Setiap baris harus berisi debit atau kredit, tidak boleh keduanya.',
                        );
                    }

                    $debitTotal += $debit;
                    $creditTotal += $credit;
                }

                if (abs($debitTotal - $creditTotal) > 0.005) {
                    $validator->errors()->add(
                        'lines',
                        'Total debit dan kredit harus seimbang.',
                    );
                }
            },
        ];
    }
}
