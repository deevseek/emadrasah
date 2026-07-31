<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teaching_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('status')->default('uploaded')->index();
            $table->unsignedInteger('sheet_count')->default(0);
            $table->unsignedInteger('subject_count')->default(0);
            $table->unsignedInteger('personnel_count')->default(0);
            $table->unsignedInteger('classroom_count')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('unmatched_rows')->default(0);
            $table->unsignedInteger('selection_rows')->default(0);
            $table->unsignedInteger('review_rows')->default(0);
            $table->json('sheet_names')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('teaching_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('teaching_import_batches')->cascadeOnDelete();
            $table->string('sheet_name');
            $table->unsignedInteger('row_number');
            $table->string('row_type')->default('reference');
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->string('status')->index();
            $table->json('messages')->nullable();
            $table->foreignId('matched_personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->foreignId('matched_subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('matched_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->timestamps();
            $table->unique(['batch_id', 'sheet_name', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_import_rows');
        Schema::dropIfExists('teaching_import_batches');
    }
};
