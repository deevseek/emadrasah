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
        $setting = $this->setting();
        if ($setting !== null) return $setting->{$column} ?? $default;
        return config('bri.'.$configKey, $default);
    }

    public function enabled(): bool { return (bool) $this->value('enabled', 'enabled', false); }
    public function environment(): string { return (string) $this->value('environment', 'environment', 'sandbox'); }
    public function baseUrl(): ?string { return $this->value('base_url', 'base_url'); }
    public function clientId(): ?string { return $this->value('client_id', 'client_id'); }
    public function clientSecret(): ?string { return $this->value('client_secret', 'client_secret'); }
    public function partnerId(): ?string { return $this->value('partner_id', 'partner_id'); }
    public function channelId(): ?string { return $this->value('channel_id', 'channel_id'); }
    public function registeredAccountNumber(): ?string { return $this->value('registered_account_number', 'registered_account_number'); }
    public function brivaEnabled(): bool { return (bool) $this->value('briva_enabled', 'briva.enabled', false); }
    public function partnerServiceId(): ?string { return $this->value('partner_service_id', 'briva.partner_service_id'); }
    public function brivaMode(): string { return (string) $this->value('briva_mode', 'briva.mode', 'per_student'); }
    public function customerNumberPrefix(): string { return (string) $this->value('customer_number_prefix', 'briva.customer_number_prefix', ''); }
    public function qrisEnabled(): bool { return (bool) $this->value('qris_enabled', 'qris.enabled', false); }
    public function merchantId(): ?string { return $this->value('merchant_id', 'qris.merchant_id'); }
    public function terminalId(): ?string { return $this->value('terminal_id', 'qris.terminal_id'); }
    public function payrollEnabled(): bool { return (bool) $this->value('payroll_enabled', 'payroll.enabled', false); }
    public function sourceAccount(): ?string { return $this->value('source_account', 'payroll.source_account'); }
    public function timeout(): int { return max(1, (int) $this->value('timeout', 'timeout_seconds', 20)); }
    public function timestampTolerance(): int { return max(1, (int) $this->value('timestamp_tolerance', 'timestamp_tolerance_seconds', 300)); }
    public function path(string $name): ?string { $column = 'path_'.$name; $value = $this->setting() ? $this->setting()?->{$column} : config('bri.paths.'.$name); return is_string($value) && $value !== '' ? $value : null; }
    public function serviceCode(string $name): ?string { $columns = ['qris'=>'qris_service_code','intrabank'=>'intrabank_service_code','interbank'=>'interbank_service_code','status_inquiry'=>'status_inquiry_service_code']; $value = $this->setting() && isset($columns[$name]) ? $this->setting()?->{$columns[$name]} : (config('bri.'.$name.'.service_code') ?? config('bri.payroll.'.$name.'_service_code')); return is_string($value) && $value !== '' ? $value : null; }
    public function privateKey(): ?string { return $this->keyContents('private_key_path', 'private_key_path'); }
    public function publicKey(): ?string { return $this->keyContents('public_key_path', 'public_key_path'); }
    public function hasPrivateKey(): bool { return $this->privateKey() !== null; }
    public function hasPublicKey(): bool { return $this->publicKey() !== null; }

    private function keyContents(string $column, string $configKey): ?string
    {
        $path = $this->setting()?->{$column};
        if ($path && Storage::disk('bri_private')->exists($path)) return Storage::disk('bri_private')->get($path);
        if ($this->setting()) return null;
        $fallback = config('bri.'.$configKey);
        return is_string($fallback) && is_file($fallback) ? file_get_contents($fallback) ?: null : null;
    }
}
