<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teaching_journals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete()->index();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete()->index();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete()->index();
            $table->foreignId('academic_subject_id')->constrained()->restrictOnDelete()->index();
            $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete()->index();
            $table->date('journal_date')->index();
            $table->string('lesson_number', 50)->nullable();
            $table->string('topic');
            $table->text('learning_objectives')->nullable();
            $table->text('learning_material')->nullable();
            $table->string('learning_method')->nullable();
            $table->text('learning_activity')->nullable();
            $table->text('assignment')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('classroom_journals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete()->index();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete()->index();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete()->index();
            $table->date('journal_date')->index();
            $table->text('agenda')->nullable();
            $table->text('classroom_condition')->nullable();
            $table->text('student_discipline')->nullable();
            $table->text('important_events')->nullable();
            $table->text('teacher_notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'journal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_journals');
        Schema::dropIfExists('teaching_journals');
    }
};
