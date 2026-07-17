<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaveType: string
{
    case Personal = 'izin';
    case Sick = 'sakit';
    case Vacation = 'cuti';
    case Duty = 'dinas';
    case Family = 'keperluan_keluarga';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Izin',
            self::Sick => 'Sakit',
            self::Vacation => 'Cuti',
            self::Duty => 'Dinas',
            self::Family => 'Keperluan Keluarga',
            self::Other => 'Lainnya',
        };
    }

    public function attendanceStatus(): AttendanceStatus
    {
        return match ($this) {
            self::Sick => AttendanceStatus::Sick,
            self::Vacation => AttendanceStatus::Vacation,
            self::Duty => AttendanceStatus::Duty,
            default => AttendanceStatus::Leave,
        };
    }
}
