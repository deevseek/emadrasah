<?php

declare(strict_types=1);

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Transferred = 'transferred';
    case Promoted = 'promoted';
    case Retained = 'retained';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Transferred => 'Pindah Kelas',
            self::Promoted => 'Naik Kelas',
            self::Retained => 'Tinggal Kelas',
            self::Withdrawn => 'Keluar',
            self::Cancelled => 'Dibatalkan',
            self::Graduated => 'Lulus',
        };
    }
}
