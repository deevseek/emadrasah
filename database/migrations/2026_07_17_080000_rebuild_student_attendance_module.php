<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('status')->default('draft')->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['classroom_id', 'attendance_date'], 'sas_class_date_unique');
        });

        Schema::table('student_attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_attendances', 'student_attendance_session_id')) {$table->foreignId('student_attendance_session_id')->nullable()->after('id')->constrained('student_attendance_sessions')->nullOnDelete();}
            if (! Schema::hasColumn('student_attendances', 'student_enrollment_id')) {$table->foreignId('student_enrollment_id')->nullable()->after('student_id')->constrained('student_enrollments')->nullOnDelete();}
            if (! Schema::hasColumn('student_attendances', 'semester_id')) {$table->foreignId('semester_id')->nullable()->after('academic_year_id')->constrained('semesters')->restrictOnDelete();}
            if (! Schema::hasColumn('student_attendances', 'arrival_time')) {$table->time('arrival_time')->nullable()->after('status');}
            if (! Schema::hasColumn('student_attendances', 'departure_time')) {$table->time('departure_time')->nullable()->after('arrival_time');}
            if (! Schema::hasColumn('student_attendances', 'late_minutes')) {$table->unsignedSmallInteger('late_minutes')->default(0)->after('departure_time');}
            if (! Schema::hasColumn('student_attendances', 'early_leave_minutes')) {$table->unsignedSmallInteger('early_leave_minutes')->default(0)->after('late_minutes');}
            if (! Schema::hasColumn('student_attendances', 'reason')) {$table->text('reason')->nullable()->after('early_leave_minutes');}
            if (! Schema::hasColumn('student_attendances', 'attachment_path')) {$table->string('attachment_path')->nullable()->after('notes');}
            if (! Schema::hasColumn('student_attendances', 'finalized_by')) {$table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();}
            if (! Schema::hasColumn('student_attendances', 'finalized_at')) {$table->timestamp('finalized_at')->nullable();}
            if (! Schema::hasColumn('student_attendances', 'corrected_by')) {$table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();}
            if (! Schema::hasColumn('student_attendances', 'corrected_at')) {$table->timestamp('corrected_at')->nullable();}
            if (! Schema::hasColumn('student_attendances', 'correction_reason')) {$table->text('correction_reason')->nullable();}
            $table->index(['student_attendance_session_id', 'status'], 'sa_session_status_idx');
        });

        Schema::create('student_attendance_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_attendance_id')->constrained('student_attendances')->restrictOnDelete();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status'); $table->string('new_status');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable();
            $table->text('reason'); $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('student_attendance_corrections');
        Schema::table('student_attendances', function (Blueprint $table): void { foreach(['sa_session_status_idx'] as $i){$table->dropIndex($i);} });
        Schema::dropIfExists('student_attendance_sessions');
    }
};
