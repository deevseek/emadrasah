<?php

declare(strict_types=1);

namespace App\Enums;

enum ClassroomProgramType: string
{
    case Regular = 'regular';
    case FullDay = 'full_day';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Reguler',
            self::FullDay => 'Full Day',
        };
    }
}
