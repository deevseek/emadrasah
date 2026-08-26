<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRfidAttendanceRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('rfid.diagnostics.enabled', false)) {
            Log::info('Permintaan absensi RFID diterima.', [
                'method' => $request->method(),
                'path' => $request->path(),
                'scheme' => $request->getScheme(),
                'host' => $request->getHost(),
                'secure' => $request->secure(),
                'x_forwarded_proto' => $this->safeHeader($request, 'X-Forwarded-Proto'),
                'x_forwarded_host' => $this->safeHeader($request, 'X-Forwarded-Host'),
                'user_agent' => $this->safeHeader($request, 'User-Agent'),
                'ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }

    private function safeHeader(Request $request, string $name): ?string
    {
        $value = $request->header($name);

        return is_string($value)
            ? mb_substr(str_replace(["\r", "\n"], '', $value), 0, 255)
            : null;
    }
}
