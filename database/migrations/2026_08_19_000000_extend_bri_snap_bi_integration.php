<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bri_integration_settings', function (Blueprint $table): void {
            $table->text('registered_account_number')->nullable();
            $table->boolean('qris_enabled')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('terminal_id')->nullable();
            $table->string('qris_service_code')->nullable();
            $table->string('intrabank_service_code')->nullable();
            $table->string('interbank_service_code')->nullable();
            $table->string('status_inquiry_service_code')->nullable();
        });
        Schema::table('bank_transactions', function (Blueprint $table): void {
            $table->string('partner_reference')->nullable()->index();
            $table->string('virtual_account_number')->nullable()->index();
            $table->string('response_code')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('last_inquired_at')->nullable();
        });
        Schema::create('bri_qris_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('student_invoices')->cascadeOnDelete();
            $table->string('partner_reference')->unique();
            $table->string('provider_reference')->nullable()->unique();
            $table->text('qr_content');
            $table->decimal('amount', 15, 2);
            $table->string('status', 30)->index();
            $table->timestamp('expires_at');
            $table->timestamp('last_inquired_at')->nullable();
            $table->timestamps();
        });
        Schema::table('payroll_disbursements', function (Blueprint $table): void {
            $table->string('partner_reference_no')->nullable()->unique();
            $table->string('response_code')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('last_inquired_at')->nullable();
        });
        Schema::table('payroll_payment_batches', fn (Blueprint $table) => $table->timestamp('completed_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('payroll_payment_batches', fn (Blueprint $table) => $table->dropColumn('completed_at'));
        Schema::table('payroll_disbursements', fn (Blueprint $table) => $table->dropColumn(['partner_reference_no', 'response_code', 'response_message', 'last_inquired_at']));
        Schema::dropIfExists('bri_qris_transactions');
        Schema::table('bank_transactions', fn (Blueprint $table) => $table->dropColumn(['partner_reference', 'virtual_account_number', 'response_code', 'response_message', 'last_inquired_at']));
        Schema::table('bri_integration_settings', fn (Blueprint $table) => $table->dropColumn(['registered_account_number', 'qris_enabled', 'merchant_id', 'terminal_id', 'qris_service_code', 'intrabank_service_code', 'interbank_service_code', 'status_inquiry_service_code']));
    }
};
