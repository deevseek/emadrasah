<?php

declare(strict_types=1);

namespace App\Enums;

enum SubjectCategory: string
{
    case General = 'umum';
    case Religion = 'keagamaan';
    case LocalContent = 'muatan_lokal';
    case SelfDevelopment = 'pengembangan_diri';
    case Other = 'lainnya';

    // Backward-compatible categories used by existing seeders/data before Modul 5 rebuild.
    case Btaq = 'btaq';
    case Extracurricular = 'ekstrakurikuler';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Umum',
            self::Religion => 'Keagamaan',
            self::LocalContent => 'Muatan Lokal',
            self::SelfDevelopment => 'Pengembangan Diri',
            self::Other => 'Lainnya',
            self::Btaq => 'BTAQ',
            self::Extracurricular => 'Ekstrakurikuler',
        };
    }
}
