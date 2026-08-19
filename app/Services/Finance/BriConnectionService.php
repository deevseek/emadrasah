<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\BriIntegrationSetting;
use Throwable;

final class BriConnectionService
{
    public function __construct(
        private readonly BriSnapBiClient $client,
        private readonly BriConfigurationService $configuration,
        private readonly BriBalanceInquiryService $balance,
    ) {}

    /** @return array{success: bool, message: string} */
    public function test(): array
    {
        try {
            $this->client->accessToken();
            $message = 'Access token B2B berhasil diperoleh.';
            if ($this->configuration->registeredAccountNumber()) {
                $this->balance->inquiry();
                $message .= ' Balance Inquiry berhasil.';
            }
            $result = ['success' => true, 'message' => $message];
        } catch (Throwable $exception) {
            report($exception);
            $result = ['success' => false, 'message' => 'Uji koneksi gagal: '.$exception->getMessage()];
        }

        BriIntegrationSetting::query()->first()?->update([
            'last_connection_at' => now(),
            'last_connection_success' => $result['success'],
            'last_connection_message' => $result['message'],
        ]);

        activity('bri-connection')->withProperties(['success' => $result['success']])->log('Menjalankan uji koneksi BRI.');

        return $result;
    }
}
