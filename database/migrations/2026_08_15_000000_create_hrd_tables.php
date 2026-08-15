<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table): void {
            $table->date('employment_start_date')->nullable()->after('employment_status');
            $table->decimal('base_salary', 15, 2)->nullable()->after('bank_account_number');
            $table->unsignedTinyInteger('default_shift_number')->default(1)->after('base_salary');
            $table->boolean('payroll_enabled')->default(true)->after('default_shift_number');
            $table->string('face_reference_path')->nullable()->after('payroll_enabled');
        });
        Schema::create('personnel_attendances', function (Blueprint $table): void {
            $table->id(); $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->date('attendance_date'); $table->unsignedTinyInteger('shift_number')->default(1);
            $table->dateTime('check_in_time')->nullable(); $table->dateTime('check_out_time')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable(); $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable(); $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->string('status')->index(); $table->string('method')->default('self')->index();
            $table->unsignedInteger('late_minutes')->default(0); $table->unsignedInteger('early_leave_minutes')->default(0); $table->unsignedInteger('overtime_minutes')->default(0);
            $table->text('note')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['personnel_id', 'attendance_date', 'shift_number'], 'personnel_attendance_unique'); $table->index(['attendance_date', 'status']);
        });
        Schema::create('personnel_attendance_late_remarks', function (Blueprint $table): void {
            $table->id(); $table->foreignId('attendance_id')->unique()->constrained('personnel_attendances')->cascadeOnDelete(); $table->text('reason'); $table->text('note')->nullable(); $table->string('attachment_path')->nullable(); $table->string('status')->default('pending')->index(); $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('decision_at')->nullable(); $table->text('decision_note')->nullable(); $table->timestamps();
        });
        Schema::create('personnel_leave_requests', function (Blueprint $table): void {
            $table->id(); $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete(); $table->date('start_date'); $table->date('end_date'); $table->string('leave_type')->index(); $table->text('reason'); $table->string('attachment_path')->nullable(); $table->string('status')->default('pending')->index(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('approved_at')->nullable(); $table->text('rejection_note')->nullable(); $table->timestamps(); $table->index(['personnel_id', 'start_date', 'end_date']);
        });
        Schema::create('personnel_payrolls', function (Blueprint $table): void {
            $table->id(); $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete(); $table->date('period_start'); $table->date('period_end'); $table->date('pay_date')->nullable()->index(); $table->decimal('monthly_salary', 15, 2); $table->decimal('base_salary', 15, 2); $table->unsignedInteger('attendance_days')->default(0); $table->decimal('daily_salary', 15, 2)->default(0); $table->decimal('allowance', 15, 2)->default(0); $table->decimal('deduction', 15, 2)->default(0); $table->decimal('late_deduction', 15, 2)->default(0); $table->decimal('cash_advance_deduction', 15, 2)->default(0); $table->decimal('total', 15, 2); $table->string('status')->default('draft')->index(); $table->text('note')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['personnel_id', 'period_start', 'period_end']); $table->index(['period_start', 'period_end']);
        });
        Schema::create('personnel_cash_advances', function (Blueprint $table): void {
            $table->id(); $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete(); $table->string('cash_advance_number')->unique(); $table->date('request_date')->index(); $table->decimal('amount', 15, 2); $table->decimal('remaining_amount', 15, 2); $table->decimal('installment_amount', 15, 2); $table->string('installment_type')->default('fixed'); $table->text('reason'); $table->text('note')->nullable(); $table->string('status')->default('pending')->index(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('approved_at')->nullable(); $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('rejected_at')->nullable(); $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('disbursed_at')->nullable(); $table->date('disbursement_date')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('personnel_cash_advance_payments', function (Blueprint $table): void {
            $table->id(); $table->foreignId('cash_advance_id')->constrained('personnel_cash_advances')->restrictOnDelete(); $table->foreignId('payroll_id')->nullable()->constrained('personnel_payrolls')->restrictOnDelete(); $table->date('payment_date'); $table->decimal('amount', 15, 2); $table->string('method')->index(); $table->text('note')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['cash_advance_id', 'payroll_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('personnel_cash_advance_payments'); Schema::dropIfExists('personnel_cash_advances'); Schema::dropIfExists('personnel_payrolls'); Schema::dropIfExists('personnel_leave_requests'); Schema::dropIfExists('personnel_attendance_late_remarks'); Schema::dropIfExists('personnel_attendances');
        Schema::table('personnel', fn (Blueprint $table) => $table->dropColumn(['employment_start_date','base_salary','default_shift_number','payroll_enabled','face_reference_path']));
    }
};
