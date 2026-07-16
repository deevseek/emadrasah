<?php

declare(strict_types=1);

namespace App\Enums;

enum BtaqJournalStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }
}
