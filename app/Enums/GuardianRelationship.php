<?php

declare(strict_types=1);

namespace App\Enums;

enum GuardianRelationship: string
{
    case Father='father'; case Mother='mother'; case Guardian='guardian'; case Grandfather='grandfather'; case Grandmother='grandmother'; case Sibling='sibling'; case Uncle='uncle'; case Aunt='aunt'; case Other='other';
    public function label(): string { return match($this){self::Father=>'Ayah',self::Mother=>'Ibu',self::Guardian=>'Wali',self::Grandfather=>'Kakek',self::Grandmother=>'Nenek',self::Sibling=>'Saudara',self::Uncle=>'Paman',self::Aunt=>'Bibi',self::Other=>'Lainnya'}; }
}
