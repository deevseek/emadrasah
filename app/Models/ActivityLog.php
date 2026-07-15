<?php

declare(strict_types=1);

namespace App\Models;

class ActivityLog extends \Spatie\Activitylog\Models\Activity
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'batch_uuid',
    ];
}
