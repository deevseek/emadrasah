<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bri_integration_settings', function (Blueprint $table): void {
            $table->unsignedInteger('timestamp_tolerance')->nullable();
            $table->unsignedInteger('timeout')->nullable();
            $table->string('qris_notification_success_code', 50)->nullable();
            $table->string('path_bank_statement')->nullable();
            $table->string('path_qris_generate')->nullable();
            $table->string('path_transaction_status')->nullable();
            $table->string('path_intrabank_transfer')->nullable();
            $table->string('path_interbank_transfer')->nullable();
            $table->boolean('direct_debit_enabled')->nullable();
            $table->timestamp('env_synced_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('bri_integration_settings', fn (Blueprint $table) => $table->dropColumn([
            'timestamp_tolerance','timeout','qris_notification_success_code','path_bank_statement','path_qris_generate',
            'path_transaction_status','path_intrabank_transfer','path_interbank_transfer','direct_debit_enabled','env_synced_at',
        ]));
    }
};
