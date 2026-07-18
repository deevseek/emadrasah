<?php

declare(strict_types=1);

namespace App\Enums;

enum AssessmentPeriodStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}
