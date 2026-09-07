<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Finance\BriConfigurationService;
use App\Services\Finance\BriCallbackAuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class VerifyBriSnapBiCallback
{
    public function __construct(private BriConfigurationService $configuration, private BriCallbackAuthenticationService $authentication) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->configuration->enabled(), 503, 'Integrasi BRI tidak aktif.');
        $timestamp = (string) $request->header('X-TIMESTAMP');
        $externalId = (string) $request->header('X-EXTERNAL-ID');
        $signature = (string) $request->header('X-SIGNATURE');
        $partner = (string) $request->header('X-PARTNER-ID');
        $channel = (string) $request->header('CHANNEL-ID');
        $bearer = (string) $request->bearerToken();

        abort_unless($timestamp && $externalId && $signature && $partner && $bearer, 401, 'Header SNAP BI tidak lengkap.');
        abort_unless($this->authentication->tokenIsValid($bearer), 401, 'Bearer token tidak valid.');
        abort_unless(hash_equals((string) $this->configuration->callbackBriClientId(), $partner), 401, 'Partner ID tidak valid.');
        $expectedChannel = (string) $this->configuration->callbackChannelId();
        abort_if($channel !== '' && ($expectedChannel === '' || ! hash_equals($expectedChannel, $channel)), 401, 'Channel ID tidak valid.');
        try { $this->authentication->validateTimestamp($timestamp); } catch (\Illuminate\Validation\ValidationException) { abort(401, 'Timestamp tidak valid.'); }

        $callbackSecret = (string) $this->configuration->callbackClientSecret();
        abort_if($callbackSecret === '', 503, 'Credential callback BRI belum dikonfigurasi.');
        $canonical = $request->routeIs('snap.bri.qris.notify') ? '/snap/v1.1/qr/qr-mpm-notify' : '/'.$request->path();
        $bodyHash = strtolower(hash('sha256', $request->getContent()));
        $stringToSign = strtoupper($request->method()).':'.$canonical.':'.$bearer.':'.$bodyHash.':'.$timestamp;
        $expected = base64_encode(hash_hmac('sha512', $stringToSign, $callbackSecret, true));
        abort_unless(hash_equals($expected, $signature), 401, 'Signature tidak valid.');
        $request->attributes->set('bri_callback_replayed', ! Cache::add('bri:callback:'.hash('sha256', $partner.'|'.$externalId), true, now()->addDay()));

        activity('bri-callback')->withProperties(['external_id_hash' => hash('sha256', $externalId), 'endpoint' => $request->path()])->log('Callback BRI terverifikasi.');

        return $next($request);
    }
}
