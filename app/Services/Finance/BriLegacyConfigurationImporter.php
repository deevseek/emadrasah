<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\BriIntegrationSetting;
use Illuminate\Support\Facades\Schema;

class BriLegacyConfigurationImporter
{
    public function importIfEmpty(): ?BriIntegrationSetting
    {
        if (! Schema::hasTable('bri_integration_settings')) return null;
        if ($existing = BriIntegrationSetting::query()->first()) return $existing;
        $data = BriEnvironmentMapper::fromConfig();
        $meaningful = collect($data)->except(['enabled','environment','timestamp_tolerance','timeout','briva_enabled','qris_enabled','payroll_enabled','direct_debit_enabled'])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
        return $meaningful ? BriIntegrationSetting::query()->create($data) : null;
    }
}
