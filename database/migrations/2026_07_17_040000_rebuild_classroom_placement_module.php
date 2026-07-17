<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            if (! Schema::hasColumn('classrooms', 'description')) {
                $table->text('description')->nullable()->after('room');
            }
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_enrollments', 'source')) {
                $table->string('source')->nullable()->after('enrollment_status');
            }
            if (! Schema::hasColumn('student_enrollments', 'processed_by')) {
                $table->foreignId('processed_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
            $table->index(['student_id', 'academic_year_id', 'enrollment_status'], 'stu_enr_active_idx');
            $table->index(['classroom_id', 'enrollment_status'], 'cls_enr_active_idx');
        });

        Schema::create('homeroom_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['classroom_id', 'is_active'], 'hr_class_active_idx');
            $table->index(['employee_id', 'academic_year_id', 'is_active'], 'hr_emp_year_active_idx');
        });

        Schema::create('promotion_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('target_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('source_classroom_id')->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('target_classroom_id')->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at');
            $table->string('status')->default('processed');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['source_classroom_id', 'target_classroom_id', 'target_academic_year_id'], 'promo_batch_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_batches');
        Schema::dropIfExists('homeroom_assignments');
        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropIndex('stu_enr_active_idx');
            $table->dropIndex('cls_enr_active_idx');
            $table->dropConstrainedForeignId('processed_by');
            $table->dropColumn(['source']);
        });
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
