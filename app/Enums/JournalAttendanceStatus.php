<?php

declare(strict_types=1);

namespace App\Enums;

enum JournalAttendanceStatus: string
{
    case Pending = 'pending';
    case Present = 'present';
    case Sick = 'sick';
    case Permitted = 'permitted';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum Tercatat',self::Present => 'Hadir',self::Sick => 'Sakit',self::Permitted => 'Izin',self::Absent => 'Alpa'
        };
    }
}
