<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportCardStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Locked = 'locked';
    case Reopened = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Locked => 'Locked',
            self::Reopened => 'Reopened',
        };
    }
}
