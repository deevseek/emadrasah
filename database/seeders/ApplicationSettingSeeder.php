<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApplicationSetting;
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Database\Seeder;

class ApplicationSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ApplicationSettingService::DEFAULTS as $key => $value) {
            ApplicationSetting::query()->firstOrCreate(['key' => $key], ['value' => is_bool($value) ? ($value ? '1' : '0') : $value, 'type' => match (true) { is_bool($value) => 'boolean', is_int($value) => 'integer', default => 'string' }, 'group' => 'general']);
        }
        app(ApplicationSettingService::class)->clearCache();
    }
}
