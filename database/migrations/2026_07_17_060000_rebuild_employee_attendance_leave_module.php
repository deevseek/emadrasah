<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('employee_type')->nullable()->index();
            $table->json('working_days');
            $table->time('check_in_time');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(0);
            $table->time('check_out_time');
            $table->time('earliest_check_in_time')->nullable();
            $table->time('earliest_check_out_time')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_work_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_schedule_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'effective_from', 'effective_until'], 'employee_schedule_effective_idx');
        });

        Schema::create('school_holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_attendances', 'work_schedule_id')) $table->foreignId('work_schedule_id')->nullable()->after('attendance_date')->constrained()->nullOnDelete();
            if (! Schema::hasColumn('employee_attendances', 'scheduled_check_in')) $table->time('scheduled_check_in')->nullable()->after('work_schedule_id');
            if (! Schema::hasColumn('employee_attendances', 'scheduled_check_out')) $table->time('scheduled_check_out')->nullable()->after('scheduled_check_in');
            if (! Schema::hasColumn('employee_attendances', 'late_minutes')) $table->unsignedSmallInteger('late_minutes')->default(0)->after('status');
            if (! Schema::hasColumn('employee_attendances', 'early_leave_minutes')) $table->unsignedSmallInteger('early_leave_minutes')->default(0)->after('late_minutes');
            if (! Schema::hasColumn('employee_attendances', 'check_in_latitude')) $table->decimal('check_in_latitude', 10, 7)->nullable();
            if (! Schema::hasColumn('employee_attendances', 'check_in_longitude')) $table->decimal('check_in_longitude', 10, 7)->nullable();
            if (! Schema::hasColumn('employee_attendances', 'check_out_latitude')) $table->decimal('check_out_latitude', 10, 7)->nullable();
            if (! Schema::hasColumn('employee_attendances', 'check_out_longitude')) $table->decimal('check_out_longitude', 10, 7)->nullable();
            if (! Schema::hasColumn('employee_attendances', 'check_in_accuracy')) $table->unsignedInteger('check_in_accuracy')->nullable();
            if (! Schema::hasColumn('employee_attendances', 'check_out_accuracy')) $table->unsignedInteger('check_out_accuracy')->nullable();
            if (! Schema::hasColumn('employee_attendances', 'check_in_photo_path')) $table->string('check_in_photo_path')->nullable();
            if (! Schema::hasColumn('employee_attendances', 'check_out_photo_path')) $table->string('check_out_photo_path')->nullable();
            if (! Schema::hasColumn('employee_attendances', 'verification_status')) $table->string('verification_status')->default('pending')->index();
            if (! Schema::hasColumn('employee_attendances', 'verification_notes')) $table->text('verification_notes')->nullable();
            if (! Schema::hasColumn('employee_attendances', 'source')) $table->string('source')->default('manual')->index();
            if (! Schema::hasColumn('employee_attendances', 'updated_by')) $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['employee_id', 'attendance_date', 'status'], 'employee_attendance_status_idx');
        });

        Schema::create('attendance_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_attendance_id')->constrained()->restrictOnDelete();
            $table->json('old_values');
            $table->json('new_values');
            $table->text('reason');
            $table->foreignId('corrected_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('corrected_at');
            $table->timestamps();
        });

        Schema::table('employee_leave_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_leave_requests', 'total_days')) $table->unsignedSmallInteger('total_days')->default(1)->after('ends_at');
            if (! Schema::hasColumn('employee_leave_requests', 'submitted_at')) $table->timestamp('submitted_at')->nullable()->after('status');
            if (! Schema::hasColumn('employee_leave_requests', 'rejected_by')) $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            if (! Schema::hasColumn('employee_leave_requests', 'rejected_at')) $table->timestamp('rejected_at')->nullable();
            if (! Schema::hasColumn('employee_leave_requests', 'cancelled_at')) $table->timestamp('cancelled_at')->nullable();
            if (! Schema::hasColumn('employee_leave_requests', 'notes')) $table->text('notes')->nullable();
            $table->index(['employee_id', 'starts_at', 'ends_at', 'status'], 'employee_leave_range_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('school_holidays');
        Schema::dropIfExists('employee_work_schedules');
        Schema::dropIfExists('work_schedules');
    }
};
