<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum DiscountType: string
{
    case Fixed = 'nominal';
    case Percentage = 'persentase';
}
