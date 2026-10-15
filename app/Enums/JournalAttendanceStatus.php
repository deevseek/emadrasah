<?php

declare(strict_types=1);

namespace App\Enums;

enum JournalAttendanceStatus: string
{
    case Present = 'present';
    case Sick = 'sick';
    case Permitted = 'permitted';
    case Absent = 'absent';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Sick => 'Sakit',
            self::Permitted => 'Izin',
            self::Absent => 'Alpa',
            self::Pending => 'Belum diisi oleh guru jam 1–2',
        };
    }
}
