<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\TransactionStatus;
use App\Models\Finance\CashAccount;
use App\Models\Finance\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FinancialTransactionService
{
    public function createAndPost(array $data, array $lines): FinancialTransaction
    {
        return DB::transaction(function () use ($data, $lines): FinancialTransaction {
            $debit = '0';
            $credit = '0';
            throw_if(count($lines) < 2, ValidationException::withMessages(['lines' => 'Minimal dua baris jurnal.']));
            foreach ($lines as $line) {
                throw_if(bccomp((string) ($line['debit'] ?? 0), '0', 2) < 0 || bccomp((string) ($line['credit'] ?? 0), '0', 2) < 0, ValidationException::withMessages(['lines' => 'Nilai tidak boleh negatif.']));
                throw_if(bccomp((string) ($line['debit'] ?? 0), '0', 2) > 0 && bccomp((string) ($line['credit'] ?? 0), '0', 2) > 0, ValidationException::withMessages(['lines' => 'Debit dan kredit tidak boleh diisi bersamaan.']));
                $debit = bcadd($debit, (string) ($line['debit'] ?? 0), 2);
                $credit = bcadd($credit, (string) ($line['credit'] ?? 0), 2);
            }
            throw_if(bccomp($debit, $credit, 2) !== 0 || bccomp($debit, '0', 2) <= 0, ValidationException::withMessages(['lines' => 'Total debit dan kredit harus sama dan lebih dari nol.']));
            $transaction = FinancialTransaction::create($data + [
                'transaction_number' => app(DocumentNumberService::class)->next('KAS', 'KAS/{YEAR}/{MONTH}/{SEQ}'),
                'status' => TransactionStatus::Posted->value,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);
            foreach ($lines as $line) {
                $transaction->lines()->create($line);
                if (! empty($line['cash_account_id'])) {
                    $cash = CashAccount::query()->lockForUpdate()->find($line['cash_account_id']);
                    $delta = bcsub((string) ($line['debit'] ?? 0), (string) ($line['credit'] ?? 0), 2);
                    $cash?->update(['current_balance' => bcadd((string) $cash->current_balance, $delta, 2)]);
                }
            }
            activity('finance')->performedOn($transaction)->causedBy(auth()->user())->event('finance.posted')->log('Transaksi keuangan diposting');

            return $transaction;
        });
    }

    public function reverse(FinancialTransaction $transaction, string $reason): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $reason): FinancialTransaction {
            $transaction = FinancialTransaction::query()->lockForUpdate()->with('lines')->findOrFail($transaction->id);
            throw_if($transaction->status === TransactionStatus::Cancelled->value, ValidationException::withMessages(['transaction' => 'Transaksi sudah dibatalkan.']));
            $lines = [];
            foreach ($transaction->lines as $line) {
                $lines[] = [
                    'chart_account_id' => $line->chart_account_id,
                    'cash_account_id' => $line->cash_account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal: '.$reason,
                ];
            }
            $reversal = $this->createAndPost([
                'transaction_date' => now()->toDateString(),
                'transaction_type' => $transaction->transaction_type,
                'description' => 'Reversal '.$transaction->transaction_number.': '.$reason,
                'reference_type' => $transaction::class,
                'reference_id' => $transaction->id,
                'created_by' => auth()->id(),
            ], $lines);
            $transaction->update([
                'status' => TransactionStatus::Cancelled->value,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $reversal;
        });
    }
}
