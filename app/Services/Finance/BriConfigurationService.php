<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\BriIntegrationSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BriConfigurationService
{
    private ?BriIntegrationSetting $setting = null;
    private bool $loaded = false;

    public function setting(): ?BriIntegrationSetting
    {
        if (! $this->loaded) {
            $this->loaded = true;
            $this->setting = Schema::hasTable('bri_integration_settings') ? BriIntegrationSetting::query()->first() : null;
        }
        return $this->setting;
    }

    private function value(string $column, string $configKey, mixed $default = null): mixed
    {
        $value = $this->setting()?->{$column};
        return $value !== null ? $value : config('bri.'.$configKey, $default);
    }

    public function enabled(): bool { return (bool) $this->value('enabled', 'enabled', false); }
    public function environment(): string { return (string) $this->value('environment', 'environment', 'sandbox'); }
    public function baseUrl(): ?string { return $this->value('base_url', 'base_url'); }
    public function clientId(): ?string { return $this->value('client_id', 'client_id'); }
    public function clientSecret(): ?string { return $this->value('client_secret', 'client_secret'); }
    public function partnerId(): ?string { return $this->value('partner_id', 'partner_id'); }
    public function channelId(): ?string { return $this->value('channel_id', 'channel_id'); }
    public function brivaEnabled(): bool { return (bool) $this->value('briva_enabled', 'briva.enabled', false); }
    public function payrollEnabled(): bool { return (bool) $this->value('payroll_enabled', 'payroll.enabled', false); }
    public function privateKey(): ?string { return $this->keyContents('private_key_path', 'private_key_path'); }
    public function publicKey(): ?string { return $this->keyContents('public_key_path', 'public_key_path'); }
    public function hasPrivateKey(): bool { return $this->privateKey() !== null; }
    public function hasPublicKey(): bool { return $this->publicKey() !== null; }

    private function keyContents(string $column, string $configKey): ?string
    {
        $path = $this->setting()?->{$column};
        if ($path && Storage::disk('bri_private')->exists($path)) return Storage::disk('bri_private')->get($path);
        $fallback = config('bri.'.$configKey);
        return is_string($fallback) && is_file($fallback) ? file_get_contents($fallback) ?: null : null;
    }
}
