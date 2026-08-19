<?php

declare(strict_types=1);

namespace App\Enums;

enum BriTransactionStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Unknown = 'unknown';
}
