<?php

declare(strict_types=1);

namespace App\Services\Finance;

use RuntimeException;

class BriQrisService
{
    public function __construct(private BriSnapBiClient $client, private BriConfigurationService $configuration) {}

    public function generate(string $reference, string|float|int $amount): array
    {
        if (!(bool) config('bri.qris.enabled', false)) throw new RuntimeException('QRIS BRI belum diaktifkan.');
        $merchantId = (string) config('bri.qris.merchant_id');
        $terminalId = (string) config('bri.qris.terminal_id');
        if ($merchantId === '' || $terminalId === '') throw new RuntimeException('Merchant ID/Terminal ID QRIS BRI belum lengkap.');
        $response = $this->client->post('/v1.0/qr-dynamic-mpm/qr-mpm-generate-qr', [
            'partnerReferenceNo'=>$reference,
            'amount'=>['value'=>number_format((float)$amount,2,'.',''),'currency'=>'IDR'],
            'merchantId'=>$merchantId,
            'terminalId'=>$terminalId,
        ]);
        return $response->json();
    }

    public function inquire(string $providerReference): array
    {
        $terminalId = (string) config('bri.qris.terminal_id');
        $response = $this->client->post('/v1.0/qr-dynamic-mpm/qr-mpm-query', [
            'originalReferenceNo'=>$providerReference,
            'serviceCode'=>(string) config('bri.qris.service_code','17'),
            'additionalInfo'=>['terminalId'=>$terminalId],
        ]);
        return $response->json();
    }
}
