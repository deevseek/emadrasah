<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Exceptions\BriApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** Reusable SNAP BI transport. It never logs credentials, tokens, bodies, or account numbers. */
final class BriSnapBiClient
{
    public function __construct(private readonly BriConfigurationService $configuration) {}

    public function accessToken(): string
    {
        if (! $this->configuration->enabled()) {
            throw new BriApiException('Integrasi BRI belum diaktifkan.');
        }

        $key = 'bri:snap-bi:token:'.hash('sha256', $this->required($this->configuration->clientId(), 'Client ID').'|'.$this->configuration->environment());
        $cached = Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $timestamp = $this->timestamp();
        $signature = '';
        if (! openssl_sign($this->required($this->configuration->clientId(), 'Client ID').'|'.$timestamp, $signature, $this->required($this->configuration->privateKey(), 'Private Key'), OPENSSL_ALGO_SHA256)) {
            throw new BriApiException('Gagal membuat signature access token BRI.');
        }

        try {
            $response = Http::acceptJson()->asJson()->timeout($this->configuration->timeout())->withHeaders([
                'X-CLIENT-KEY' => $this->configuration->clientId(),
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => base64_encode($signature),
            ])->post($this->url($this->configuration->path('access_token')), ['grantType' => 'client_credentials']);
        } catch (ConnectionException $exception) {
            throw new BriApiException('Koneksi access token BRI mengalami timeout.', outcomeUnknown: false, previous: $exception);
        }

        $this->ensureSuccessful($response, 'autentikasi');
        $token = $response->json('accessToken');
        if (! is_string($token) || $token === '') {
            throw new BriApiException('BRI tidak mengembalikan access token yang valid.', httpStatus: $response->status());
        }
        $expiresIn = filter_var($response->json('expiresIn'), FILTER_VALIDATE_INT) ?: 900;
        Cache::put($key, $token, now()->addSeconds(max(30, $expiresIn - 60)));

        return $token;
    }

    /** @param array<string, mixed> $body */
    public function post(string $path, array $body, ?string $externalId = null): Response
    {
        $path = '/'.ltrim($this->required($path, 'Path endpoint'), '/');
        $timestamp = $this->timestamp();
        $token = $this->accessToken();
        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        try {
            $response = Http::acceptJson()->timeout($this->configuration->timeout())->withToken($token)->withHeaders([
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $this->requestSignature('POST', $path, $token, $json, $timestamp),
                'X-PARTNER-ID' => $this->required($this->configuration->partnerId(), 'Partner ID'),
                'CHANNEL-ID' => $this->required($this->configuration->channelId(), 'Channel ID'),
                'X-EXTERNAL-ID' => $externalId ?: str_replace('-', '', (string) Str::uuid()),
            ])->withBody($json, 'application/json')->post($this->url($path));
        } catch (ConnectionException $exception) {
            // A timed-out financial POST can have reached BRI. Caller must inquire, never blindly retry.
            throw new BriApiException('Koneksi BRI timeout; status transaksi harus diperiksa.', outcomeUnknown: true, previous: $exception);
        }

        $this->ensureSuccessful($response, 'permintaan API');

        return $response;
    }

    public function requestSignature(string $method, string $path, string $token, string $rawBody, string $timestamp): string
    {
        $hash = strtolower(hash('sha256', $rawBody));
        $canonical = strtoupper($method).':/'.ltrim($path, '/').':'.$token.':'.$hash.':'.$timestamp;

        return base64_encode(hash_hmac('sha512', $canonical, $this->required($this->configuration->clientSecret(), 'Client Secret'), true));
    }

    private function timestamp(): string { return now()->format('Y-m-d\\TH:i:sP'); }
    private function url(string $path): string { return rtrim($this->required($this->configuration->baseUrl(), 'Base URL'), '/').'/'.ltrim($path, '/'); }
    private function required(?string $value, string $name): string
    {
        if (! is_string($value) || trim($value) === '') throw new BriApiException($name.' BRI belum dikonfigurasi.');
        return $value;
    }

    private function ensureSuccessful(Response $response, string $operation): void
    {
        if ($response->successful()) return;
        $code = $response->json('responseCode');
        $message = $response->json('responseMessage');
        throw new BriApiException(
            'BRI menolak '.$operation.($message ? ': '.Str::limit((string) $message, 200) : '.'),
            is_string($code) ? $code : null,
            $response->status(),
        );
    }
}
