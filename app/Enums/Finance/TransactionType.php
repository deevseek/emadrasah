<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum TransactionType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
    case OpeningBalance = 'opening_balance';
}
