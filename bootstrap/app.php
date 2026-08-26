<?php

use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\EnsureApplicationIsAvailable;
use App\Http\Middleware\AuthenticateRfidDevice;
use App\Http\Middleware\LogRfidAttendanceRequest;
use App\Http\Middleware\VerifyBriSnapBiCallback;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\AttendanceSecurityException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = config('rfid.trusted_proxies', []);
        if ($trustedProxies !== []) {
            $middleware->trustProxies(
                at: $trustedProxies,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_PREFIX,
            );
        }
        $middleware->appendToGroup('web', EnsureApplicationIsAvailable::class);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'permission' => EnsureUserHasPermission::class,
            'force-password-change' => ForcePasswordChange::class,
            'rfid.device' => AuthenticateRfidDevice::class,
            'rfid.attendance.diagnostics' => LogRfidAttendanceRequest::class,
            'bri.callback' => VerifyBriSnapBiCallback::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AttendanceSecurityException $exception, Request $request) {
            if ($request->expectsJson()) return response()->json(['error'=>['code'=>$exception->errorCode,'message'=>$exception->getMessage()]],$exception->status);
            return back()->withErrors(['attendance'=>$exception->getMessage()]);
        });
    })->create();
