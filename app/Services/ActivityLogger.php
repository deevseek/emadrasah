<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $event, ?Model $subject = null, array $old = [], array $new = [], ?string $description = null): void
    {
        $clean = fn (array $values) => collect($values)->except(['password', 'remember_token', 'token', 'api_key', 'session'])->all();
        ActivityLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'old_values' => $clean($old),
            'new_values' => $clean($new),
            'description' => $description,
        ]);
    }
}
