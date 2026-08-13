<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Settings\ApplicationSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsAvailable
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $this->settings->get('maintenance_mode', false) || $request->user()->hasRole('super-admin')) return $next($request);

        return response()->view('errors.maintenance', ['message' => $this->settings->get('maintenance_message')], 503);
    }
}
