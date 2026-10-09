<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_devices', function (Blueprint $table): void {
            $table->timestamp('last_heartbeat_at')->nullable()->index();
            $table->string('mac_address', 17)->nullable();
            $table->string('wifi_bssid', 17)->nullable();
            $table->string('wifi_gateway', 15)->nullable();
            $table->string('wifi_subnet', 15)->nullable();
            $table->unsignedTinyInteger('wifi_channel')->nullable();
            $table->unsignedInteger('free_heap')->nullable();
            $table->unsignedInteger('wifi_disconnect_count')->nullable();
            $table->unsignedSmallInteger('wifi_disconnect_reason')->nullable();
        });
        // MAC terakhir saja tidak mendeteksi perangkat fisik yang bergantian
        // memakai Device ID sama. Simpan pengamatan identitas tanpa token.
        Schema::create('rfid_device_mac_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rfid_device_id')->constrained('rfid_devices')->cascadeOnDelete();
            $table->string('mac_address', 17);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->unique(['rfid_device_id', 'mac_address'], 'rfid_device_mac_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_device_mac_observations');
        Schema::table('rfid_devices', fn (Blueprint $table) => $table->dropIndex(['last_heartbeat_at']));
        Schema::table('rfid_devices', fn (Blueprint $table) => $table->dropColumn([
            'last_heartbeat_at', 'mac_address', 'wifi_bssid', 'wifi_gateway',
            'wifi_subnet', 'wifi_channel', 'free_heap', 'wifi_disconnect_count', 'wifi_disconnect_reason',
        ]));
    }
};
