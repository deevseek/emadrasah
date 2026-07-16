<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Paid = 'paid';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
