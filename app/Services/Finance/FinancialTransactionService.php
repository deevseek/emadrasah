<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\TransactionStatus;
use App\Models\Finance\CashAccount;
use App\Models\Finance\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialTransactionService
{
    public function createAndPost(array $data, array $lines): FinancialTransaction
    {
        return DB::transaction(function () use ($data, $lines): FinancialTransaction {
            $debit = '0'; $credit = '0';
            foreach ($lines as $line) {
                if (bccomp((string) ($line['debit'] ?? 0), '0', 2) > 0 && bccomp((string) ($line['credit'] ?? 0), '0', 2) > 0) {
                    throw ValidationException::withMessages(['lines' => 'Debit dan kredit tidak boleh diisi bersamaan.']);
                }
                $debit = bcadd($debit, (string) ($line['debit'] ?? 0), 2);
                $credit = bcadd($credit, (string) ($line['credit'] ?? 0), 2);
            }
            throw_if(bccomp($debit, $credit, 2) !== 0, ValidationException::withMessages(['lines' => 'Total debit dan kredit harus sama.']));
            $transaction = FinancialTransaction::create($data + [
                'transaction_number' => app(DocumentNumberService::class)->next('KAS', 'KAS/{YEAR}/{MONTH}/{SEQ}'),
                'status' => TransactionStatus::Posted->value,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);
            foreach ($lines as $line) {
                $transaction->lines()->create($line);
                if (! empty($line['cash_account_id'])) {
                    $cash = CashAccount::lockForUpdate()->find($line['cash_account_id']);
                    $delta = bcsub((string) ($line['debit'] ?? 0), (string) ($line['credit'] ?? 0), 2);
                    $cash?->update(['current_balance' => bcadd((string) $cash->current_balance, $delta, 2)]);
                }
            }
            activity('finance')->performedOn($transaction)->causedBy(auth()->user())->event('finance.posted')->log('Transaksi keuangan diposting');

            return $transaction;
        });
    }
}
