<?php

declare(strict_types=1);

namespace App\Enums;

enum GuardianRelationship: string
{
    case Father = 'father';
    case Mother = 'mother';
    case Guardian = 'guardian';
    case Stepfather = 'stepfather';
    case Stepmother = 'stepmother';
    case Grandfather = 'grandfather';
    case Grandmother = 'grandmother';
    case Sibling = 'sibling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Father => 'Ayah Kandung', self::Mother => 'Ibu Kandung', self::Guardian => 'Wali',
            self::Stepfather => 'Ayah Tiri', self::Stepmother => 'Ibu Tiri', self::Grandfather => 'Kakek',
            self::Grandmother => 'Nenek', self::Sibling => 'Saudara', self::Other => 'Lainnya',
        };
    }
}
