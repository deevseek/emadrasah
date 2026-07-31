<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teaching_assignment_sets', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name'); $table->string('status')->default('draft')->index(); $table->string('source')->default('manual');
            $table->string('source_filename')->nullable(); $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable(); $table->timestamps();
        });
        Schema::create('teaching_assignments', function (Blueprint $table): void {
            $table->id(); $table->foreignId('assignment_set_id')->constrained('teaching_assignment_sets')->cascadeOnDelete(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete(); $table->foreignId('subject_id')->constrained()->restrictOnDelete(); $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->unsignedTinyInteger('weekly_periods'); $table->string('teacher_role')->default('primary'); $table->boolean('is_primary')->default(true); $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->index(['assignment_set_id', 'personnel_id']); $table->index(['assignment_set_id', 'classroom_id']);
        });
        Schema::create('additional_duties', function (Blueprint $table): void {
            $table->id(); $table->foreignId('assignment_set_id')->constrained('teaching_assignment_sets')->cascadeOnDelete(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->string('duty_type'); $table->string('duty_name'); $table->foreignId('classroom_id')->nullable()->constrained()->restrictOnDelete(); $table->unsignedTinyInteger('equivalent_periods')->default(0); $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['assignment_set_id', 'personnel_id', 'classroom_id', 'duty_type'], 'additional_duty_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('additional_duties'); Schema::dropIfExists('teaching_assignments'); Schema::dropIfExists('teaching_assignment_sets'); }
};
