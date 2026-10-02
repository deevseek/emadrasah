<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmbm_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('applicant_id')->constrained('pmbm_applicants')->cascadeOnDelete();
            $table->string('payment_stage', 24)->index();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 40);
            $table->string('reference_number', 100)->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamp('paid_at');
            $table->string('status', 20)->default('pending')->index();
            $table->text('verification_note')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['applicant_id', 'status']);
        });
        Schema::table('pmbm_decisions', function (Blueprint $table): void {
            $table->string('acceptance_letter_number', 120)->nullable()->unique()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('pmbm_decisions', fn (Blueprint $table) => $table->dropColumn('acceptance_letter_number'));
        Schema::dropIfExists('pmbm_payments');
    }
};
