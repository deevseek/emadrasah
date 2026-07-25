<?php

declare(strict_types=1);

namespace App\Enums;

enum ScheduleEntryType: string
{
    case Lesson = 'lesson';
    case Activity = 'activity';
    case Break = 'break';

    public function label(): string
    {
        return match ($this) { self::Lesson => 'Pelajaran', self::Activity => 'Kegiatan', self::Break => 'Istirahat/Pulang' };
    }
}
