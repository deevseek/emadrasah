<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Graduated = 'graduated';
    case Transferred = 'transferred';
    case Withdrawn = 'withdrawn';
    case Deceased = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Tidak Aktif',
            self::Graduated => 'Lulus',
            self::Transferred => 'Pindah',
            self::Withdrawn => 'Keluar',
            self::Deceased => 'Meninggal',
        };
    }

    public function blocksActiveEnrollment(): bool
    {
        return in_array($this, [self::Inactive, self::Graduated, self::Transferred, self::Withdrawn, self::Deceased], true);
    }
}
