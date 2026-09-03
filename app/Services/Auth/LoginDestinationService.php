<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

class LoginDestinationService
{
    public function routeName(User $user): string
    {
        return match (true) {
            $user->hasRole('orang-tua') => 'parent.dashboard',
            $user->hasRole('bendahara') => 'finance.dashboard',
            $user->hasRole('hrd') => 'hrd.dashboard',
            $user->hasRole('guru') && $user->personnel()->exists() => 'personnel.profile.show',
            $user->can('dashboard.view') => 'dashboard',
            $user->personnel()->exists() => 'personnel.profile.show',
            $user->can('finance.dashboard.view') => 'finance.dashboard',
            $user->can('hrd.dashboard.view') => 'hrd.dashboard',
            $user->can('parent.dashboard.view') => 'parent.dashboard',
            default => 'password.change',
        };
    }
}
