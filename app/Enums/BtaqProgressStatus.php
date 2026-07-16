<?php

declare(strict_types=1);

namespace App\Enums;

enum BtaqProgressStatus: string
{
    case NotAssessed = 'not_assessed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NeedsGuidance = 'needs_guidance';

    public function label(): string
    {
        return match ($this) {
            self::NotAssessed => 'Not Assessed',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::NeedsGuidance => 'Needs Guidance',
        };
    }
}
