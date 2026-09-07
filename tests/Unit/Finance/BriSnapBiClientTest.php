<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Services\Finance\BriConfigurationService;
use App\Services\Finance\BriSnapBiClient;
use Illuminate\Http\Client\PendingRequest;
use Mockery;
use Tests\TestCase;

final class BriSnapBiClientTest extends TestCase
{
    public function test_http_client_uses_proxy_only_when_configured(): void
    {
        $configuration = Mockery::mock(BriConfigurationService::class);
        $configuration->shouldReceive('timeout')->andReturn(20);
        $client = new BriSnapBiClient($configuration);
        $method = new \ReflectionMethod($client, 'http');

        config(['bri.http_proxy' => 'http://10.77.0.1:3128']);
        /** @var PendingRequest $proxied */
        $proxied = $method->invoke($client);
        $this->assertSame('http://10.77.0.1:3128', $proxied->getOptions()['proxy']);

        config(['bri.http_proxy' => null]);
        /** @var PendingRequest $direct */
        $direct = $method->invoke($client);
        $this->assertArrayNotHasKey('proxy', $direct->getOptions());
    }

    public function test_timestamp_has_milliseconds_and_offset_and_external_id_is_numeric(): void
    {
        $client = new BriSnapBiClient(Mockery::mock(BriConfigurationService::class));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}[+-]\d{2}:\d{2}$/', $client->timestamp());
        $this->assertMatchesRegularExpression('/^\d{1,36}$/', $client->externalId());
        $this->assertNotSame($client->externalId(), $client->externalId());
    }
}
