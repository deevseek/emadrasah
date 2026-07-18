<?php

declare(strict_types=1);

namespace App\Enums\OperationalFinance;

enum OperationalTransactionStatus: string
{
    case Draft = 'draft'; case Submitted = 'submitted'; case Approved = 'approved'; case Rejected = 'rejected'; case Posted = 'posted'; case Cancelled = 'cancelled';
    public function label(): string { return match($this){self::Draft=>'Draft', self::Submitted=>'Diajukan', self::Approved=>'Disetujui', self::Rejected=>'Ditolak', self::Posted=>'Dibukukan', self::Cancelled=>'Dibatalkan'}; }
}
