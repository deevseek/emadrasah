<?php

declare(strict_types=1);

namespace App\Enums;

enum SubjectCategory: string
{
    case General = 'umum'; case Religion = 'agama'; case LocalContent = 'muatan_lokal'; case Btaq = 'btaq'; case Extracurricular = 'ekstrakurikuler';
    public function label(): string { return str($this->value)->replace('_', ' ')->title()->toString(); }
}
