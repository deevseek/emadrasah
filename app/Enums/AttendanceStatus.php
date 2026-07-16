<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'hadir';
    case Late = 'terlambat';
    case Leave = 'izin';
    case Sick = 'sakit';
    case Duty = 'dinas';
    case Alpha = 'alpha';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late => 'Terlambat',
            self::Leave => 'Izin',
            self::Sick => 'Sakit',
            self::Duty => 'Dinas',
            self::Alpha => 'Alpha',
        };
    }
}
