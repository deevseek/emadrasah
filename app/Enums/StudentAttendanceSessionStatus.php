<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentAttendanceSessionStatus: string
{
    case Draft = 'draft'; case Final = 'final'; case NeedsRevision = 'perlu_perbaikan';
    public function label(): string { return match($this){self::Draft=>'Draft',self::Final=>'Final',self::NeedsRevision=>'Perlu Perbaikan'}; }
}
