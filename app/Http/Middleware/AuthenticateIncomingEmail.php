<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIncomingEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('email-service.inbound_token');
        $providedToken = (string) $request->header('X-Inbound-Email-Token');

        abort_if($configuredToken === '' || ! hash_equals($configuredToken, $providedToken), 401);

        return $next($request);
    }
}
