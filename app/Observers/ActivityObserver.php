<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Notifications\ModuleNotificationService;
use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    public function created(Activity $activity): void
    {
        app(ModuleNotificationService::class)->publish($activity);
    }
}
