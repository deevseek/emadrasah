<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_subjects', function (Blueprint $table): void {
            $table->id(); $table->string('code', 30)->nullable(); $table->string('name'); $table->boolean('is_active')->default(true)->index(); $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $table->foreignId('semester_id')->constrained()->restrictOnDelete(); $table->foreignId('classroom_id')->constrained()->restrictOnDelete(); $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date'); $table->string('status', 20); $table->text('notes')->nullable(); $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['student_id','classroom_id','attendance_date']); $table->index(['academic_year_id','semester_id','classroom_id','attendance_date'], 'attendance_filters');
        });
        Schema::create('student_grades', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $table->foreignId('semester_id')->constrained()->restrictOnDelete(); $table->foreignId('classroom_id')->constrained()->restrictOnDelete(); $table->foreignId('student_id')->constrained()->restrictOnDelete(); $table->foreignId('academic_subject_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2)->nullable(); $table->text('notes')->nullable(); $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['semester_id','classroom_id','student_id','academic_subject_id'], 'grade_unique'); $table->index(['academic_year_id','semester_id','classroom_id','academic_subject_id'], 'grade_filters');
        });
    }
    public function down(): void { Schema::dropIfExists('student_grades'); Schema::dropIfExists('student_attendances'); Schema::dropIfExists('academic_subjects'); }
};
