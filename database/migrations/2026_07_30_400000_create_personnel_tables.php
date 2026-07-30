<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 200)->index();
            $table->string('gender')->index();
            $table->string('birth_place', 150)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('employment_status')->index();
            $table->string('foundation_employee_number', 50)->nullable()->unique();
            $table->string('nip', 50)->nullable()->unique();
            $table->string('rank_grade', 200)->nullable();
            $table->string('external_employee_id', 100)->nullable()->unique();
            $table->string('last_education', 100)->nullable();
            $table->string('position', 150)->index();
            $table->string('certification_status', 100)->nullable()->index();
            $table->string('certification_subject', 150)->nullable();
            $table->unsignedSmallInteger('weekly_teaching_hours')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 200)->nullable()->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('personnel_import_batches', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename'); $table->string('stored_filename'); $table->string('status')->index();
            $table->string('duplicate_strategy');
            foreach (['total_rows','valid_rows','warning_rows','invalid_rows','duplicate_rows','imported_rows','updated_rows','skipped_rows','failed_rows'] as $column) $table->unsignedInteger($column)->default(0);
            $table->timestamp('started_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->json('summary')->nullable(); $table->timestamps();
        });
        Schema::create('personnel_import_rows', function (Blueprint $table): void {
            $table->id(); $table->foreignId('batch_id')->constrained('personnel_import_batches')->cascadeOnDelete(); $table->unsignedInteger('row_number');
            $table->json('raw_data'); $table->json('normalized_data'); $table->string('status')->index(); $table->json('messages')->nullable();
            $table->foreignId('matched_personnel_id')->nullable()->constrained('personnel')->nullOnDelete(); $table->timestamps();
            $table->unique(['batch_id','row_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('personnel_import_rows'); Schema::dropIfExists('personnel_import_batches'); Schema::dropIfExists('personnel'); }
};
