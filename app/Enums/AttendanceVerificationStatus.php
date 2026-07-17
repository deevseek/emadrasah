<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case NeedsCorrection = 'needs_correction';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
            self::NeedsCorrection => 'Perlu Koreksi',
        };
    }
}
