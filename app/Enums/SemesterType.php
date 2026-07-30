<?php

declare(strict_types=1);

namespace App\Enums;

enum SemesterType: string
{
    case Ganjil = 'ganjil';
    case Genap = 'genap';

    public function label(): string
    {
        return match ($this) {
            self::Ganjil => 'Semester Ganjil',
            self::Genap => 'Semester Genap',
        };
    }
}
