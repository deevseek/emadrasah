<?php

declare(strict_types=1);

namespace App\Enums;

enum RfidDeviceType: string
{
    case Reader = 'reader';
    case Writer = 'writer';

    public function label(): string
    {
        return match ($this) {
            self::Reader => 'Reader Absensi',
            self::Writer => 'Writer Kartu',
        };
    }
}
