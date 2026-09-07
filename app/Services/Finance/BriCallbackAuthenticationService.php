<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BriCallbackAuthenticationService
{
    public function __construct(private BriConfigurationService $configuration) {}

    public function issue(string $clientKey, string $timestamp, string $signature, string $grantType): array
    {
        if ($grantType !== 'client_credentials') $this->invalid('grantType tidak valid.');
        $configuredClient = (string) $this->configuration->callbackBriClientId();
        if ($configuredClient === '' || ! hash_equals($configuredClient, $clientKey)) $this->invalid('Client key tidak valid.');
        $this->validateTimestamp($timestamp);

        $publicKey = $this->configuration->callbackBriPublicKey();
        $decoded = base64_decode($signature, true);
        if (! is_string($publicKey) || $publicKey === '' || $decoded === false || openssl_verify($clientKey.'|'.$timestamp, $decoded, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            $this->invalid('Signature tidak valid.');
        }

        $token = bin2hex(random_bytes(32));
        $ttl = max(60, (int) config('bri.callback.token_ttl_seconds', 900));
        Cache::put($this->tokenKey($token), true, now()->addSeconds($ttl));

        return ['accessToken' => $token, 'tokenType' => 'Bearer', 'expiresIn' => $ttl];
    }

    public function tokenIsValid(string $token): bool
    {
        return $token !== '' && Cache::has($this->tokenKey($token));
    }

    public function validateTimestamp(string $timestamp): void
    {
        try {
            $delta = abs(CarbonImmutable::parse($timestamp)->diffInSeconds(now(), false));
        } catch (Throwable) {
            $this->invalid('Timestamp tidak valid.');
        }
        if ($delta > $this->configuration->timestampTolerance()) $this->invalid('Timestamp kedaluwarsa.');
    }

    private function tokenKey(string $token): string { return 'bri:callback:token:'.hash('sha256', $token); }
    private function invalid(string $message): never { throw ValidationException::withMessages(['callback' => $message]); }
}
