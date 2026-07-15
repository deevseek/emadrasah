<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $event, ?Model $subject = null, array $old = [], array $new = [], ?string $description = null): void
    {
        $clean = fn (array $values): array => collect($values)
            ->except(['password', 'remember_token', 'token', 'api_key', 'session'])
            ->all();

        activity('foundation')
            ->causedBy(Auth::user())
            ->performedOn($subject)
            ->event($event)
            ->withProperties([
                'old' => $clean($old),
                'new' => $clean($new),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log($description ?? $event);
    }
}
