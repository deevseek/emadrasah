<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Low-level outbound BRI SNAP BI client.
 * Credentials and private keys are resolved through BriConfigurationService.
 */
class BriSnapBiClient
{
    public function __construct(private BriConfigurationService $configuration) {}

    public function accessToken(): string
    {
        if (! $this->configuration->enabled()) throw new RuntimeException('Integrasi BRI belum diaktifkan.');

        return Cache::remember($this->tokenCacheKey(), now()->addMinutes(13), function (): string {
            $clientId = $this->required($this->configuration->clientId(), 'Client ID');
            $privateKey = $this->required($this->configuration->privateKey(), 'Private Key');
            $timestamp = $this->timestamp();
            $signature = '';

            if (! openssl_sign($clientId.'|'.$timestamp, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Gagal membuat signature SNAP BI untuk access token.');
            }

            $response = Http::asJson()->acceptJson()->timeout(20)->withHeaders([
                'X-CLIENT-KEY' => $clientId,
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => base64_encode($signature),
            ])->post($this->url('/snap/v1.0/access-token/b2b'), ['grantType' => 'client_credentials']);

            $this->ensureSuccessful($response, 'mengambil access token');
            $token = $response->json('accessToken');
            if (! is_string($token) || $token === '') throw new RuntimeException('BRI tidak mengembalikan access token yang valid.');
            return $token;
        });
    }

    /** @param array<string,mixed> $body */
    public function post(string $path, array $body, ?string $externalId = null): Response
    {
        $timestamp = $this->timestamp();
        $token = $this->accessToken();
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $signature = $this->symmetricSignature('POST', $path, $token, $bodyJson, $timestamp);

        $response = $this->request()->withToken($token)->withHeaders([
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
            'X-PARTNER-ID' => $this->required($this->configuration->partnerId(), 'Partner ID'),
            'CHANNEL-ID' => $this->required($this->configuration->channelId(), 'Channel ID'),
            'X-EXTERNAL-ID' => $externalId ?: (string) Str::uuid(),
        ])->withBody($bodyJson, 'application/json')->post($this->url($path));

        $this->ensureSuccessful($response, 'memanggil endpoint '.$path);
        return $response;
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(20)->retry(2, 250, throw: false);
    }

    private function symmetricSignature(string $method, string $path, string $token, string $rawBody, string $timestamp): string
    {
        $secret = $this->required($this->configuration->clientSecret(), 'Client Secret');
        $bodyHash = strtolower(hash('sha256', $rawBody));
        $stringToSign = strtoupper($method).':'.$path.':'.$token.':'.$bodyHash.':'.$timestamp;
        return base64_encode(hash_hmac('sha512', $stringToSign, $secret, true));
    }

    private function timestamp(): string { return now()->format('Y-m-d\\TH:i:sP'); }
    private function url(string $path): string { return rtrim($this->required($this->configuration->baseUrl(), 'Base URL'), '/').'/'.ltrim($path, '/'); }
    private function required(?string $value, string $label): string { if (! is_string($value) || trim($value) === '') throw new RuntimeException($label.' BRI belum dikonfigurasi.'); return $value; }
    private function tokenCacheKey(): string { return 'bri:snap-bi:token:'.sha1((string) $this->configuration->clientId().'|'.$this->configuration->environment()); }

    private function ensureSuccessful(Response $response, string $operation): void
    {
        if ($response->successful()) return;
        $message = $response->json('responseMessage') ?: $response->json('message') ?: 'HTTP '.$response->status();
        throw new RuntimeException('Gagal '.$operation.': '.$message);
    }
}
