<?php

declare(strict_types=1);

namespace App\Enums;

enum Gender: string
{
    case Male = 'laki_laki';
    case Female = 'perempuan';

    public function label(): string { return match ($this) { self::Male => 'Laki-laki', self::Female => 'Perempuan' }; }
}
