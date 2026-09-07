<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_settings')) {
            return;
        }

        $setting = DB::table('application_settings')->where('key', 'hrd_max_late_checkin_hours')->first();

        if ($setting) {
            DB::table('application_settings')->where('key', 'hrd_max_late_checkin_hours')->update([
                'key' => 'hrd_max_late_checkin_minutes',
                'value' => (string) ((int) $setting->value * 60),
                'type' => 'integer',
                'updated_at' => now(),
            ]);
        }

        Cache::forget('application_settings.all');
    }

    public function down(): void
    {
        if (! Schema::hasTable('application_settings')) {
            return;
        }

        $setting = DB::table('application_settings')->where('key', 'hrd_max_late_checkin_minutes')->first();

        if ($setting) {
            DB::table('application_settings')->where('key', 'hrd_max_late_checkin_minutes')->update([
                'key' => 'hrd_max_late_checkin_hours',
                'value' => (string) intdiv((int) $setting->value, 60),
                'type' => 'integer',
                'updated_at' => now(),
            ]);
        }
        Cache::forget('application_settings.all');
    }
};
