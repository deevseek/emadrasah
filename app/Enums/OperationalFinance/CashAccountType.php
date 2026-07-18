<?php

declare(strict_types=1);

namespace App\Enums\OperationalFinance;

enum CashAccountType: string
{
    case Cash = 'cash'; case Bank = 'bank'; case PettyCash = 'petty_cash'; case Other = 'other';
    public function label(): string { return match($this){self::Cash=>'Kas Tunai', self::Bank=>'Rekening Bank', self::PettyCash=>'Kas Kecil', self::Other=>'Lainnya'}; }
}
