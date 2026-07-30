<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 200)->index();
            $table->string('nisn', 30)->nullable()->unique();
            $table->string('nik', 30)->nullable()->unique();
            $table->string('birth_place', 150)->nullable();
            $table->date('birth_date')->nullable()->index();
            $table->string('classroom_label', 200)->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->string('gender')->index();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('special_needs', 200)->nullable();
            $table->string('disability', 200)->nullable();
            $table->string('kip_pip_number', 100)->nullable()->unique();
            $table->string('father_name', 200)->nullable()->index();
            $table->string('mother_name', 200)->nullable()->index();
            $table->string('guardian_name', 200)->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('student_import_batches', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename'); $table->string('stored_filename'); $table->string('status')->index(); $table->string('duplicate_strategy')->default('skip');
            foreach (['total_rows', 'valid_rows', 'warning_rows', 'invalid_rows', 'duplicate_rows', 'imported_rows', 'updated_rows', 'skipped_rows', 'failed_rows'] as $column) $table->unsignedInteger($column)->default(0);
            $table->timestamp('started_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->json('summary')->nullable(); $table->timestamps();
        });
        Schema::create('student_import_rows', function (Blueprint $table): void {
            $table->id(); $table->foreignId('batch_id')->constrained('student_import_batches')->cascadeOnDelete(); $table->unsignedInteger('row_number');
            $table->json('raw_data'); $table->json('normalized_data'); $table->string('status')->index(); $table->json('messages')->nullable();
            $table->foreignId('matched_student_id')->nullable()->constrained('students')->nullOnDelete(); $table->timestamps(); $table->unique(['batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_import_rows'); Schema::dropIfExists('student_import_batches'); Schema::dropIfExists('students');
    }
};
