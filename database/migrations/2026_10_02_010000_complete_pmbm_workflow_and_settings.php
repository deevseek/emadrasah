<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pmbm_settings', function (Blueprint $table): void {
            $table->text('registration_instructions')->nullable();
            $table->json('allowed_payment_methods')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->text('payment_instructions')->nullable();
            $table->boolean('payment_proof_required')->default(true);
            $table->boolean('require_child_interview')->default(true);
            $table->timestamp('announcement_at')->nullable();
        });

        Schema::table('pmbm_document_requirements', function (Blueprint $table): void {
            $table->foreignId('setting_id')->nullable()->constrained('pmbm_settings')->cascadeOnDelete();
            $table->string('channel', 10)->default('both');
            $table->dropUnique(['code']);
            $table->unique(['setting_id', 'code']);
        });

        Schema::table('pmbm_applicant_documents', function (Blueprint $table): void {
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true)->index();
            $table->foreignId('replaces_document_id')->nullable()->constrained('pmbm_applicant_documents')->nullOnDelete();
        });

        Schema::create('pmbm_child_interviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained('pmbm_applicants')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('summary')->nullable();
            $table->text('public_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();
        });

        Schema::table('pmbm_decisions', function (Blueprint $table): void {
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('result_emailed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pmbm_decisions', fn (Blueprint $table) => $table->dropConstrainedForeignId('published_by'));
        Schema::table('pmbm_decisions', fn (Blueprint $table) => $table->dropConstrainedForeignId('finalized_by'));
        Schema::table('pmbm_decisions', fn (Blueprint $table) => $table->dropColumn(['finalized_at', 'published_at', 'result_emailed_at']));
        Schema::dropIfExists('pmbm_child_interviews');
        Schema::table('pmbm_applicant_documents', fn (Blueprint $table) => $table->dropConstrainedForeignId('replaces_document_id'));
        Schema::table('pmbm_applicant_documents', fn (Blueprint $table) => $table->dropColumn(['version', 'is_current']));
        Schema::table('pmbm_document_requirements', function (Blueprint $table): void {
            $table->dropUnique(['setting_id', 'code']);
            $table->dropConstrainedForeignId('setting_id');
            $table->dropColumn('channel');
            $table->unique('code');
        });
        Schema::table('pmbm_settings', fn (Blueprint $table) => $table->dropColumn([
            'registration_instructions', 'allowed_payment_methods', 'bank_name', 'bank_account_number',
            'bank_account_name', 'payment_instructions', 'payment_proof_required', 'require_child_interview', 'announcement_at',
        ]));
    }
};
