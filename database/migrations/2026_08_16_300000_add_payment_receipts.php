<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table): void {
            $table->string('receipt_verification_token', 64)->nullable()->unique()->after('metadata');
            $table->json('receipt_snapshot')->nullable()->after('receipt_verification_token');
            $table->timestamp('receipt_generated_at')->nullable()->after('receipt_snapshot');
        });
        Schema::create('payment_receipt_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_payment_id')->constrained('student_payments')->cascadeOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('guardian_profiles')->nullOnDelete();
            $table->string('email');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['student_payment_id', 'email']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payment_receipt_deliveries');
        Schema::table('student_payments', fn (Blueprint $table) => $table->dropColumn(['receipt_verification_token', 'receipt_snapshot', 'receipt_generated_at']));
    }
};
