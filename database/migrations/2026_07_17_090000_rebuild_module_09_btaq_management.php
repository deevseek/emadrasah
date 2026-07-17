<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('btaq_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('name'); $table->string('code')->unique(); $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index(); $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('uses_reading_progress')->default(true); $table->boolean('uses_memorization_progress')->default(false);
            $table->boolean('uses_tajwid_assessment')->default(true); $table->boolean('uses_makhraj_assessment')->default(true);
            $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::table('btaq_levels', function (Blueprint $table): void {
            if (! Schema::hasColumn('btaq_levels', 'btaq_program_id')) { $table->foreignId('btaq_program_id')->nullable()->after('id')->constrained('btaq_programs')->nullOnDelete(); }
            if (! Schema::hasColumn('btaq_levels', 'sort_order')) { $table->unsignedSmallInteger('sort_order')->default(0)->index(); }
            if (! Schema::hasColumn('btaq_levels', 'minimum_score')) { $table->decimal('minimum_score', 5, 2)->nullable(); }
            if (! Schema::hasColumn('btaq_levels', 'notes')) { $table->text('notes')->nullable(); }
        });
        Schema::table('btaq_materials', function (Blueprint $table): void {
            if (! Schema::hasColumn('btaq_materials', 'btaq_program_id')) { $table->foreignId('btaq_program_id')->nullable()->after('id')->constrained('btaq_programs')->nullOnDelete(); }
            if (! Schema::hasColumn('btaq_materials', 'title')) { $table->string('title')->nullable(); }
            if (! Schema::hasColumn('btaq_materials', 'material_type')) { $table->string('material_type')->nullable()->index(); }
            if (! Schema::hasColumn('btaq_materials', 'target')) { $table->text('target')->nullable(); }
            if (! Schema::hasColumn('btaq_materials', 'notes')) { $table->text('notes')->nullable(); }
        });
        Schema::table('btaq_groups', function (Blueprint $table): void {
            if (! Schema::hasColumn('btaq_groups', 'btaq_program_id')) { $table->foreignId('btaq_program_id')->nullable()->after('semester_id')->constrained('btaq_programs')->nullOnDelete(); }
            if (! Schema::hasColumn('btaq_groups', 'teacher_employee_id')) { $table->foreignId('teacher_employee_id')->nullable()->after('code')->constrained('employees')->nullOnDelete(); }
            if (! Schema::hasColumn('btaq_groups', 'room')) { $table->string('room')->nullable(); }
            if (! Schema::hasColumn('btaq_groups', 'start_date')) { $table->date('start_date')->nullable(); }
            if (! Schema::hasColumn('btaq_groups', 'end_date')) { $table->date('end_date')->nullable(); }
        });
        Schema::table('btaq_group_students', function (Blueprint $table): void {
            if (! Schema::hasColumn('btaq_group_students', 'student_enrollment_id')) { $table->foreignId('student_enrollment_id')->nullable()->after('student_id')->constrained('student_enrollments')->nullOnDelete(); }
            if (! Schema::hasColumn('btaq_group_students', 'left_at')) { $table->date('left_at')->nullable()->after('joined_at'); }
            if (! Schema::hasColumn('btaq_group_students', 'assigned_by')) { $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete(); }
        });
        Schema::create('btaq_schedules', function (Blueprint $table): void {
            $table->id(); $table->foreignId('btaq_group_id')->constrained()->cascadeOnDelete(); $table->unsignedTinyInteger('day_of_week')->index();
            $table->time('start_time'); $table->time('end_time'); $table->string('room')->nullable(); $table->foreignId('teacher_employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('effective_start_date')->nullable(); $table->date('effective_end_date')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['teacher_employee_id','day_of_week','is_active'], 'btaq_schedule_teacher_day_index');
        });
        Schema::create('btaq_sessions', function (Blueprint $table): void {
            $table->id(); $table->foreignId('btaq_group_id')->constrained()->cascadeOnDelete(); $table->foreignId('btaq_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('teacher_employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('session_date'); $table->time('scheduled_start_time')->nullable(); $table->time('scheduled_end_time')->nullable(); $table->time('actual_start_time')->nullable(); $table->time('actual_end_time')->nullable(); $table->unsignedSmallInteger('meeting_number')->nullable();
            $table->foreignId('btaq_material_id')->nullable()->constrained()->nullOnDelete(); $table->string('topic')->nullable(); $table->text('learning_target')->nullable(); $table->text('learning_activity')->nullable(); $table->text('learning_method')->nullable(); $table->text('evaluation')->nullable(); $table->text('obstacles')->nullable(); $table->text('follow_up')->nullable(); $table->text('teacher_notes')->nullable();
            $table->string('status')->default('draft')->index(); $table->timestamp('submitted_at')->nullable(); $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('finalized_at')->nullable(); $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('verified_at')->nullable(); $table->text('verification_notes')->nullable(); $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('rejected_at')->nullable(); $table->text('rejection_reason')->nullable(); $table->foreignId('created_by')->constrained('users')->restrictOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['btaq_schedule_id','session_date'], 'btaq_session_schedule_date_unique');
        });
        Schema::create('btaq_session_attendances', function (Blueprint $table): void {
            $table->id(); $table->foreignId('btaq_session_id')->constrained()->cascadeOnDelete(); $table->foreignId('btaq_group_student_id')->constrained()->restrictOnDelete(); $table->foreignId('student_id')->constrained()->restrictOnDelete(); $table->string('status')->default('present')->index(); $table->time('arrival_time')->nullable(); $table->text('notes')->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['btaq_session_id','student_id'], 'btaq_attendance_session_student_unique');
        });
        Schema::create('btaq_student_progress', function (Blueprint $table): void {
            $table->id(); $table->foreignId('btaq_session_id')->constrained()->cascadeOnDelete(); $table->foreignId('student_id')->constrained()->restrictOnDelete(); $table->foreignId('btaq_group_id')->constrained()->restrictOnDelete(); $table->foreignId('btaq_program_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('previous_level_id')->nullable()->constrained('btaq_levels')->nullOnDelete(); $table->foreignId('current_level_id')->nullable()->constrained('btaq_levels')->nullOnDelete(); $table->foreignId('btaq_material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reading_reference')->nullable(); $table->unsignedSmallInteger('page_start')->nullable(); $table->unsignedSmallInteger('page_end')->nullable(); $table->string('surah_name')->nullable(); $table->unsignedSmallInteger('verse_start')->nullable(); $table->unsignedSmallInteger('verse_end')->nullable(); $table->string('reading_fluency')->nullable(); $table->decimal('tajwid_score',5,2)->nullable(); $table->decimal('makhraj_score',5,2)->nullable(); $table->decimal('memorization_score',5,2)->nullable(); $table->decimal('writing_score',5,2)->nullable(); $table->string('achievement_status')->default('mulai_berkembang')->index(); $table->text('teacher_notes')->nullable(); $table->text('next_target')->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('recorded_at')->nullable(); $table->timestamps(); $table->unique(['btaq_session_id','student_id'], 'btaq_progress_session_student_unique');
        });
        Schema::create('btaq_progress_histories', function (Blueprint $table): void { $table->id(); $table->foreignId('btaq_student_progress_id')->nullable()->constrained('btaq_student_progress')->nullOnDelete(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('from_level_id')->nullable()->constrained('btaq_levels')->nullOnDelete(); $table->foreignId('to_level_id')->nullable()->constrained('btaq_levels')->nullOnDelete(); $table->date('effective_date')->nullable(); $table->text('reason')->nullable(); $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->timestamps(); });
    }
    public function down(): void
    {
        Schema::dropIfExists('btaq_progress_histories'); Schema::dropIfExists('btaq_student_progress'); Schema::dropIfExists('btaq_session_attendances'); Schema::dropIfExists('btaq_sessions'); Schema::dropIfExists('btaq_schedules');
    }
};
