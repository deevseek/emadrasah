<?php

declare(strict_types=1);

namespace App\Enums\OperationalFinance;

enum OperationalTransactionType: string
{
    case Income = 'income'; case Expense = 'expense'; case TransferIn = 'transfer_in'; case TransferOut = 'transfer_out'; case Adjustment = 'adjustment';
    public function label(): string { return match($this){self::Income=>'Pemasukan', self::Expense=>'Pengeluaran', self::TransferIn=>'Transfer Masuk', self::TransferOut=>'Transfer Keluar', self::Adjustment=>'Penyesuaian'}; }
}
