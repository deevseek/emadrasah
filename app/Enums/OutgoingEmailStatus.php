<?php

declare(strict_types=1);

namespace App\Enums;

enum OutgoingEmailStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Queued => 'Menunggu',
            self::Sent => 'Terkirim',
            self::Failed => 'Gagal',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-muted',
            self::Queued => 'badge-warning',
            self::Sent => 'badge-success',
            self::Failed => 'badge-danger',
        };
    }
}
