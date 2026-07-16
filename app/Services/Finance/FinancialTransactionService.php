<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\TransactionStatus;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FinancialTransactionService
{
    public function createAndPost(array $data, array $lines): FinancialTransaction
    {
        return DB::transaction(function () use ($data, $lines): FinancialTransaction {
            $this->validateLines($lines);

            $transaction = FinancialTransaction::create($data + [
                'transaction_number' => app(DocumentNumberService::class)
                    ->next('KAS', 'KAS/{YEAR}/{MONTH}/{SEQ}'),
                'status' => TransactionStatus::Posted->value,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $transaction->lines()->create($line);
                $this->applyCashMovement($line);
            }

            activity('finance')
                ->performedOn($transaction)
                ->causedBy(auth()->user())
                ->event('finance.posted')
                ->log('Transaksi keuangan diposting');

            return $transaction->load('lines');
        });
    }

    public function reverse(
        FinancialTransaction $transaction,
        string $reason,
    ): FinancialTransaction {
        return DB::transaction(function () use ($transaction, $reason): FinancialTransaction {
            $transaction = FinancialTransaction::query()
                ->lockForUpdate()
                ->with('lines')
                ->findOrFail($transaction->getKey());

            if ($transaction->status === TransactionStatus::Cancelled->value) {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi sudah dibatalkan.',
                ]);
            }

            $lines = $transaction->lines
                ->map(static fn ($line): array => [
                    'chart_account_id' => $line->chart_account_id,
                    'cash_account_id' => $line->cash_account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal: '.$reason,
                ])
                ->all();

            $reversal = $this->createAndPost([
                'transaction_date' => now()->toDateString(),
                'transaction_type' => $transaction->transaction_type,
                'description' => 'Reversal '.$transaction->transaction_number.': '.$reason,
                'reference_type' => $transaction::class,
                'reference_id' => $transaction->getKey(),
                'created_by' => auth()->id(),
            ], $lines);

            $transaction->update([
                'status' => TransactionStatus::Cancelled->value,
                'reversal_transaction_id' => $reversal->getKey(),
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            activity('finance')
                ->performedOn($transaction)
                ->causedBy(auth()->user())
                ->withProperties([
                    'reason' => $reason,
                    'reversal_transaction_id' => $reversal->getKey(),
                ])
                ->event('finance.cancelled')
                ->log('Transaksi keuangan dibatalkan');

            return $reversal;
        });
    }

    private function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Minimal dua baris jurnal.',
            ]);
        }

        $debitTotal = '0';
        $creditTotal = '0';

        foreach ($lines as $index => $line) {
            $debit = (string) ($line['debit'] ?? 0);
            $credit = (string) ($line['credit'] ?? 0);

            if (! ChartAccount::query()->whereKey($line['chart_account_id'] ?? null)->exists()) {
                throw ValidationException::withMessages([
                    "lines.{$index}.chart_account_id" => 'Akun jurnal tidak ditemukan.',
                ]);
            }

            if (bccomp($debit, '0', 2) < 0 || bccomp($credit, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Nilai debit dan kredit tidak boleh negatif.',
                ]);
            }

            $hasDebit = bccomp($debit, '0', 2) > 0;
            $hasCredit = bccomp($credit, '0', 2) > 0;

            if ($hasDebit === $hasCredit) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Setiap baris harus berisi debit atau kredit, tidak boleh keduanya.',
                ]);
            }

            $debitTotal = bcadd($debitTotal, $debit, 2);
            $creditTotal = bcadd($creditTotal, $credit, 2);
        }

        if (bccomp($debitTotal, $creditTotal, 2) !== 0 || bccomp($debitTotal, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'lines' => 'Total debit dan kredit harus sama dan lebih dari nol.',
            ]);
        }
    }

    private function applyCashMovement(array $line): void
    {
        if (empty($line['cash_account_id'])) {
            return;
        }

        $cashAccount = CashAccount::query()
            ->lockForUpdate()
            ->findOrFail($line['cash_account_id']);

        $delta = bcsub(
            (string) ($line['debit'] ?? 0),
            (string) ($line['credit'] ?? 0),
            2,
        );

        $cashAccount->update([
            'current_balance' => bcadd(
                (string) $cashAccount->current_balance,
                $delta,
                2,
            ),
        ]);
    }
}
