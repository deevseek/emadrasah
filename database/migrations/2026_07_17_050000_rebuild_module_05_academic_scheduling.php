<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            if (! Schema::hasColumn('subjects', 'short_name')) $table->string('short_name', 50)->nullable()->after('name');
            if (! Schema::hasColumn('subjects', 'default_weekly_hours')) $table->unsignedTinyInteger('default_weekly_hours')->nullable()->after('minimum_passing_grade');
            if (! Schema::hasColumn('subjects', 'sort_order')) $table->unsignedSmallInteger('sort_order')->default(0)->index()->after('default_weekly_hours');
        });
        Schema::create('grade_level_subject', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grade_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['grade_level_id', 'subject_id'], 'gl_subject_unique');
        });
        Schema::table('teaching_assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('teaching_assignments', 'starts_on')) $table->date('starts_on')->nullable()->after('is_active');
            if (! Schema::hasColumn('teaching_assignments', 'ends_on')) $table->date('ends_on')->nullable()->after('starts_on');
            if (! Schema::hasColumn('teaching_assignments', 'notes')) $table->text('notes')->nullable()->after('ends_on');
            if (! Schema::hasColumn('teaching_assignments', 'replaced_by_id')) $table->foreignId('replaced_by_id')->nullable()->after('notes')->constrained('teaching_assignments')->nullOnDelete();
            $table->index(['academic_year_id', 'semester_id', 'classroom_id', 'subject_id', 'is_active'], 'ta_period_idx');
        });
        Schema::create('teaching_assignment_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('new_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::table('lesson_schedules', function (Blueprint $table): void {
            if (! Schema::hasColumn('lesson_schedules', 'teaching_assignment_id')) $table->foreignId('teaching_assignment_id')->nullable()->after('id')->constrained()->nullOnDelete();
            if (! Schema::hasColumn('lesson_schedules', 'lesson_hours')) $table->unsignedTinyInteger('lesson_hours')->default(1)->after('ends_at');
            if (! Schema::hasColumn('lesson_schedules', 'notes')) $table->text('notes')->nullable()->after('is_active');
            $table->index(['semester_id', 'day_of_week', 'employee_id', 'starts_at', 'ends_at'], 'ls_teacher_conflict_idx');
            $table->index(['semester_id', 'day_of_week', 'classroom_id', 'starts_at', 'ends_at'], 'ls_class_conflict_idx');
            $table->index(['semester_id', 'day_of_week', 'room', 'starts_at', 'ends_at'], 'ls_room_conflict_idx');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('teaching_assignment_histories');
        Schema::dropIfExists('grade_level_subject');
        Schema::table('lesson_schedules', function (Blueprint $table): void { $table->dropIndex('ls_teacher_conflict_idx'); $table->dropIndex('ls_class_conflict_idx'); $table->dropIndex('ls_room_conflict_idx'); });
    }
};
