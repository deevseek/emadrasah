<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\RfidDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRfidDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = (string) $request->header('X-Device-Id');
        $token = (string) $request->header('X-Device-Token');
        $device = $deviceId !== '' && $token !== '' ? RfidDevice::query()->where('device_id', $deviceId)->where('is_active', true)->first() : null;
        if (! $device || ! hash_equals($device->token_hash, hash('sha256', $token))) {
            return response()->json(['success' => false, 'code' => 'DEVICE_UNAUTHORIZED', 'message' => 'Kredensial perangkat tidak valid.'], 401);
        }
        $device->update(['last_seen_at' => now()]);
        $request->attributes->set('rfid_device', $device);
        return $next($request);
    }
}
