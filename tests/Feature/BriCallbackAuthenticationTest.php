<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class BriCallbackAuthenticationTest extends TestCase
{
    private string $privateKey;

    protected function setUp(): void
    {
        parent::setUp();
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        $privateKey = '';
        openssl_pkey_export($key, $privateKey);
        $this->privateKey = $privateKey;

        $publicKey = openssl_pkey_get_details($key)['key'];
        $path = storage_path('framework/testing-bri-callback-public.pem');
        file_put_contents($path, $publicKey);
        config([
            'bri.callback.bri_client_id' => 'BRI-CALLBACK',
            'bri.callback.bri_public_key_path' => $path,
            'bri.callback.token_ttl_seconds' => 900,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('framework/testing-bri-callback-public.pem'));
        parent::tearDown();
    }

    public function test_token_endpoint_rejects_invalid_signature(): void
    {
        $this->postJson('/snap/v1.0/access-token/b2b', ['grantType' => 'client_credentials'], [
            'X-CLIENT-KEY' => 'BRI-CALLBACK', 'X-TIMESTAMP' => now()->format('c'), 'X-SIGNATURE' => base64_encode('salah'),
        ])->assertUnprocessable();
    }

    public function test_token_endpoint_accepts_rsa_signature_and_caches_hashed_token_with_ttl(): void
    {
        $timestamp = now()->format('c');
        openssl_sign('BRI-CALLBACK|'.$timestamp, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $response = $this->postJson('/snap/v1.0/access-token/b2b', ['grantType' => 'client_credentials'], [
            'X-CLIENT-KEY' => 'BRI-CALLBACK', 'X-TIMESTAMP' => $timestamp, 'X-SIGNATURE' => base64_encode($signature),
        ])->assertOk()->assertJson(['tokenType' => 'Bearer', 'expiresIn' => 900]);

        $token = (string) $response->json('accessToken');
        $this->assertSame(64, strlen($token));
        $this->assertTrue(Cache::has('bri:callback:token:'.hash('sha256', $token)));
        $this->assertFalse(Cache::has('bri:callback:token:'.$token));
    }
}
