<?php

declare(strict_types=1);

namespace App\Enums;

enum TeachingJournalStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft', self::Submitted => 'Dikirim', self::Verified => 'Terverifikasi', self::Rejected => 'Perlu Perbaikan', self::Cancelled => 'Dibatalkan',
        };
    }
}
