<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum ApprovalStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
