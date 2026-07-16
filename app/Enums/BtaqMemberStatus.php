<?php

declare(strict_types=1);

namespace App\Enums;

enum BtaqMemberStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Transferred = 'transferred';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Transferred => 'Transferred',
        };
    }
}
