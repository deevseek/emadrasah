<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission,
    ): Response {
        $user = $request->user();
        $permissions = array_filter(explode('|', $permission));
        $isAllowed = $user !== null
            && collect($permissions)->contains(
                static fn (string $permission): bool => $user->can($permission),
            );

        abort_unless($isAllowed, 403);

        return $next($request);
    }
}
