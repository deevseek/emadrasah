<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bri_integration_settings', function (Blueprint $table): void {
            $table->string('path_qris_inquiry')->nullable();
            $table->string('callback_bri_client_id')->nullable();
            $table->string('callback_bri_public_key_path')->nullable();
            $table->text('callback_client_secret')->nullable();
            $table->string('callback_channel_id', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bri_integration_settings', fn (Blueprint $table) => $table->dropColumn([
            'path_qris_inquiry', 'callback_bri_client_id', 'callback_bri_public_key_path', 'callback_client_secret', 'callback_channel_id',
        ]));
    }
};
