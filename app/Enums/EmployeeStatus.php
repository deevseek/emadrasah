<?php

declare(strict_types=1);

namespace App\Enums;

enum EmployeeStatus: string
{
    case Permanent = 'tetap';
    case NonPermanent = 'tidak_tetap';
    case Honorary = 'honorer';
    case Contract = 'kontrak';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
