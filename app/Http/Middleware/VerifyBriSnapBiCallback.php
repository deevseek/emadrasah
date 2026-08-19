<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Finance\BriConfigurationService;
use App\Services\Finance\BriSnapBiClient;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class VerifyBriSnapBiCallback
{
    public function __construct(private BriConfigurationService $configuration, private BriSnapBiClient $client) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->configuration->enabled(), 503, 'Integrasi BRI tidak aktif.');
        $timestamp = (string) $request->header('X-TIMESTAMP');
        $externalId = (string) $request->header('X-EXTERNAL-ID');
        $signature = (string) $request->header('X-SIGNATURE');
        $partner = (string) $request->header('X-PARTNER-ID');
        $channel = (string) $request->header('CHANNEL-ID');
        $bearer = (string) $request->bearerToken();

        abort_unless($timestamp && $externalId && $signature && $bearer, 401, 'Header SNAP BI tidak lengkap.');
        abort_unless(hash_equals((string) $this->configuration->partnerId(), $partner), 401, 'Partner ID tidak valid.');
        abort_unless(hash_equals((string) $this->configuration->channelId(), $channel), 401, 'Channel ID tidak valid.');
        try {
            $delta = abs(CarbonImmutable::parse($timestamp)->diffInSeconds(now(), false));
        } catch (Throwable) {
            abort(401, 'Timestamp tidak valid.');
        }
        abort_if($delta > (int) config('bri.timestamp_tolerance_seconds', 300), 401, 'Timestamp kedaluwarsa.');

        $expected = $this->client->requestSignature($request->method(), '/'.$request->path(), $bearer, $request->getContent(), $timestamp);
        abort_unless(hash_equals($expected, $signature), 401, 'Signature tidak valid.');
        $request->attributes->set('bri_callback_replayed', ! Cache::add('bri:callback:'.hash('sha256', $partner.'|'.$externalId), true, now()->addDay()));

        activity('bri-callback')->withProperties(['external_id_hash' => hash('sha256', $externalId), 'endpoint' => $request->path()])->log('Callback BRI terverifikasi.');

        return $next($request);
    }
}
