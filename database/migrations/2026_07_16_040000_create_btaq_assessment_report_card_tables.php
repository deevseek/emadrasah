<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('btaq_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sequence')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('btaq_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('btaq_level_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->index();
            $table->unsignedSmallInteger('sequence')->index();
            $table->text('target_description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('btaq_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('btaq_level_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['academic_year_id', 'code']);
            $table->index(['semester_id', 'employee_id']);
        });
        Schema::create('btaq_group_students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('btaq_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('joined_at');
            $table->date('completed_at')->nullable();
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
        });
        Schema::create('btaq_journals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('btaq_group_id')->constrained()->cascadeOnDelete();
            $table->date('journal_date');
            $table->unsignedSmallInteger('session_number')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->foreignId('btaq_material_id')->nullable()->constrained()->nullOnDelete();
            $table->text('general_notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['btaq_group_id', 'journal_date', 'session_number']);
        });
        Schema::create('btaq_journal_students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('btaq_journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('attendance_status')->default('present');
            $table->unsignedSmallInteger('current_page')->nullable();
            $table->string('current_volume')->nullable();
            $table->string('surah')->nullable();
            $table->unsignedSmallInteger('verse_from')->nullable();
            $table->unsignedSmallInteger('verse_to')->nullable();
            $table->decimal('reading_score', 5, 2)->nullable();
            $table->decimal('writing_score', 5, 2)->nullable();
            $table->decimal('tajwid_score', 5, 2)->nullable();
            $table->decimal('memorization_score', 5, 2)->nullable();
            $table->decimal('fluency_score', 5, 2)->nullable();
            $table->string('progress_status')->default('not_assessed')->index();
            $table->text('achievement_notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamps();
            $table->unique(['btaq_journal_id', 'student_id']);
        });
        Schema::create('predicate_ranges', function (Blueprint $table): void {
            $table->id(); $table->string('code')->unique(); $table->string('label'); $table->decimal('minimum_score',5,2); $table->decimal('maximum_score',5,2); $table->text('description_template')->nullable(); $table->unsignedSmallInteger('sequence'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('assessment_components', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('semester_id')->constrained()->cascadeOnDelete(); $table->foreignId('classroom_id')->constrained()->cascadeOnDelete(); $table->foreignId('subject_id')->constrained()->restrictOnDelete(); $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete(); $table->foreignId('employee_id')->constrained()->restrictOnDelete(); $table->string('name'); $table->string('type')->index(); $table->decimal('weight',5,2); $table->decimal('maximum_score',5,2); $table->date('assessment_date')->nullable(); $table->text('description')->nullable(); $table->boolean('is_required')->default(true); $table->string('status')->default('draft')->index(); $table->timestamp('published_at')->nullable(); $table->foreignId('created_by')->constrained('users')->restrictOnDelete(); $table->timestamps(); $table->index(['classroom_id','subject_id','semester_id']);
        });
        Schema::create('student_scores', function (Blueprint $table): void {
            $table->id(); $table->foreignId('assessment_component_id')->constrained()->cascadeOnDelete(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->decimal('score',5,2)->nullable(); $table->decimal('remedial_score',5,2)->nullable(); $table->decimal('final_score',5,2)->nullable(); $table->string('predicate')->nullable(); $table->text('notes')->nullable(); $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['assessment_component_id','student_id']);
        });
        Schema::create('report_cards', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete(); $table->foreignId('classroom_id')->constrained()->cascadeOnDelete(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('semester_id')->constrained()->cascadeOnDelete(); $table->foreignId('homeroom_teacher_id')->nullable()->constrained('employees')->nullOnDelete(); $table->string('document_number'); $table->string('status')->default('draft')->index(); $table->timestamp('generated_at')->nullable(); $table->timestamp('submitted_at')->nullable(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('approved_at')->nullable(); $table->timestamp('locked_at')->nullable(); $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('reopened_at')->nullable(); $table->text('homeroom_notes')->nullable(); $table->text('general_notes')->nullable(); $table->string('place')->nullable(); $table->date('report_date')->nullable(); $table->unsignedSmallInteger('sick_count')->default(0); $table->unsignedSmallInteger('permission_count')->default(0); $table->unsignedSmallInteger('alpha_count')->default(0); $table->unsignedSmallInteger('late_count')->default(0); $table->timestamps(); $table->unique(['student_id','academic_year_id','semester_id']);
        });
        Schema::create('report_card_subjects', function (Blueprint $table): void { $table->id(); $table->foreignId('report_card_id')->constrained()->cascadeOnDelete(); $table->foreignId('subject_id')->constrained()->restrictOnDelete(); $table->decimal('final_score',5,2); $table->string('predicate')->nullable(); $table->unsignedTinyInteger('minimum_passing_grade')->nullable(); $table->text('achievement_description'); $table->text('notes')->nullable(); $table->unsignedSmallInteger('sequence'); $table->timestamps(); $table->unique(['report_card_id','subject_id']); });
        Schema::create('report_card_btaq', function (Blueprint $table): void { $table->id(); $table->foreignId('report_card_id')->unique()->constrained()->cascadeOnDelete(); $table->foreignId('btaq_level_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('last_material_id')->nullable()->constrained('btaq_materials')->nullOnDelete(); $table->decimal('final_score',5,2)->nullable(); $table->string('predicate')->nullable(); $table->text('achievement_description')->nullable(); $table->text('development_notes')->nullable(); $table->boolean('needs_guidance')->default(false); $table->timestamps(); });
        Schema::create('report_card_status_histories', function (Blueprint $table): void { $table->id(); $table->foreignId('report_card_id')->constrained()->cascadeOnDelete(); $table->string('from_status')->nullable(); $table->string('to_status'); $table->text('reason')->nullable(); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); });
        Schema::create('student_attitude_notes', function (Blueprint $table): void { $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('classroom_id')->constrained()->cascadeOnDelete(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('semester_id')->constrained()->cascadeOnDelete(); $table->text('spiritual_notes')->nullable(); $table->text('social_notes')->nullable(); $table->text('discipline_notes')->nullable(); $table->text('responsibility_notes')->nullable(); $table->text('general_notes')->nullable(); $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['student_id','academic_year_id','semester_id']); });
        Schema::create('extracurriculars', function (Blueprint $table): void { $table->id(); $table->string('code')->unique(); $table->string('name'); $table->foreignId('supervisor_employee_id')->nullable()->constrained('employees')->nullOnDelete(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('student_extracurriculars', function (Blueprint $table): void { $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('extracurricular_id')->constrained()->cascadeOnDelete(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('semester_id')->constrained()->cascadeOnDelete(); $table->string('predicate')->nullable(); $table->text('description')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['student_id','extracurricular_id','academic_year_id','semester_id'], 'student_extra_unique'); });
        Schema::create('student_achievements', function (Blueprint $table): void { $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete(); $table->string('achievement_type'); $table->string('name'); $table->string('level'); $table->string('rank')->nullable(); $table->date('achievement_date')->nullable(); $table->text('description')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); });
    }
    public function down(): void
    {
        foreach (['student_achievements','student_extracurriculars','extracurriculars','student_attitude_notes','report_card_status_histories','report_card_btaq','report_card_subjects','report_cards','student_scores','assessment_components','predicate_ranges','btaq_journal_students','btaq_journals','btaq_group_students','btaq_groups','btaq_materials','btaq_levels'] as $table) { Schema::dropIfExists($table); }
    }
};
