<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum PaymentMethod: string
{
    case Cash = 'tunai';
    case BankTransfer = 'transfer_bank';
    case Other = 'lainnya';
}
