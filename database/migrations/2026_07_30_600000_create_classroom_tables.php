<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table): void {
            $table->id(); $table->unsignedTinyInteger('number')->unique(); $table->string('name'); $table->string('roman_label', 10); $table->unsignedTinyInteger('sort_order'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->string('code', 50); $table->string('name', 150)->nullable(); $table->unsignedSmallInteger('capacity')->nullable(); $table->string('room_name', 100)->nullable();
            $table->foreignId('homeroom_personnel_id')->nullable()->constrained('personnel')->nullOnDelete(); $table->boolean('is_active')->default(true)->index(); $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['academic_year_id', 'grade_level_id', 'code']); $table->index('academic_year_id'); $table->index('grade_level_id'); $table->index('homeroom_personnel_id');
        });
        Schema::create('classroom_memberships', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->restrictOnDelete(); $table->foreignId('classroom_id')->constrained()->restrictOnDelete(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('status'); $table->date('joined_at'); $table->date('left_at')->nullable(); $table->text('notes')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'status']); $table->index(['classroom_id', 'status']); $table->index(['academic_year_id', 'status']);
        });
        Schema::create('legacy_classroom_mappings', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $table->string('legacy_label', 255); $table->foreignId('classroom_id')->constrained()->restrictOnDelete(); $table->unsignedInteger('mapped_students_count')->default(0); $table->foreignId('mapped_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('mapped_at')->nullable(); $table->timestamps(); $table->unique(['academic_year_id', 'legacy_label']);
        });
        Schema::create('classroom_promotion_batches', function (Blueprint $table): void {
            $table->id(); $table->foreignId('source_academic_year_id')->constrained('academic_years')->restrictOnDelete(); $table->foreignId('target_academic_year_id')->constrained('academic_years')->restrictOnDelete(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('status')->default('draft');
            foreach (['total_rows','ready_rows','excluded_rows','processed_rows','failed_rows'] as $column) $table->unsignedInteger($column)->default(0); $table->timestamp('started_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->json('summary')->nullable(); $table->timestamps();
        });
        Schema::create('classroom_promotion_rows', function (Blueprint $table): void {
            $table->id(); $table->foreignId('batch_id')->constrained('classroom_promotion_batches')->cascadeOnDelete(); $table->foreignId('student_id')->constrained()->restrictOnDelete(); $table->foreignId('source_classroom_id')->constrained('classrooms')->restrictOnDelete(); $table->foreignId('target_classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete(); $table->string('action'); $table->string('status'); $table->text('notes')->nullable(); $table->json('messages')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('classroom_promotion_rows'); Schema::dropIfExists('classroom_promotion_batches'); Schema::dropIfExists('legacy_classroom_mappings'); Schema::dropIfExists('classroom_memberships'); Schema::dropIfExists('classrooms'); Schema::dropIfExists('grade_levels'); }
};
