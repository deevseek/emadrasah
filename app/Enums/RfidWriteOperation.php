<?php

declare(strict_types=1);

namespace App\Enums;

enum RfidWriteOperation: string
{
    case Write = 'write';
    case Rewrite = 'rewrite';
    case Reassign = 'reassign';

    public function replacesExisting(): bool
    {
        return $this !== self::Write;
    }
}
