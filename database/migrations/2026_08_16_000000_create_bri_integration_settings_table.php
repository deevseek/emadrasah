<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bri_integration_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->nullable();
            $table->string('environment', 20)->nullable();
            $table->string('base_url')->nullable();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('partner_id')->nullable();
            $table->string('channel_id', 50)->nullable();
            $table->string('private_key_path')->nullable();
            $table->string('public_key_path')->nullable();
            $table->boolean('briva_enabled')->nullable();
            $table->string('briva_mode', 30)->nullable();
            $table->string('partner_service_id')->nullable();
            $table->string('institution_code')->nullable();
            $table->string('customer_number_prefix')->nullable();
            $table->boolean('payroll_enabled')->nullable();
            $table->text('source_account')->nullable();
            $table->string('payroll_method', 30)->nullable();
            $table->timestamp('last_connection_at')->nullable();
            $table->boolean('last_connection_success')->nullable();
            $table->string('last_connection_message')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('bri_integration_settings'); }
};
