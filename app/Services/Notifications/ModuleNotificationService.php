<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\ModuleActivityNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;

class ModuleNotificationService
{
    public function publish(Activity $activity): void
    {
        $module = config("notifications.modules.{$activity->log_name}");

        if (! is_array($module)) {
            return;
        }

        $actorId = $activity->causer_type === User::class ? (int) $activity->causer_id : null;
        $permissions = $module['permissions'] ?? [];
        $recipients = User::query()
            ->where('is_active', true)
            ->when($actorId, fn ($query) => $query->whereKeyNot($actorId))
            ->with('roles.permissions')
            ->get()
            ->filter(fn (User $user): bool => $user->hasRole('super-admin') || collect($permissions)->contains(fn (string $permission): bool => $user->can($permission)));

        if ($recipients->isEmpty()) {
            return;
        }

        $routeName = (string) ($module['route'] ?? 'dashboard');
        Notification::send($recipients, new ModuleActivityNotification([
            'module' => (string) $activity->log_name,
            'module_label' => (string) ($module['label'] ?? 'Sistem'),
            'message' => $activity->description,
            'actor' => $activity->causer?->name,
            'url' => Route::has($routeName) ? route($routeName) : route('dashboard'),
            'activity_id' => $activity->id,
        ]));
    }
}
