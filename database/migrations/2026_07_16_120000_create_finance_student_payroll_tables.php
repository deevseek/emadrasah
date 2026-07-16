<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id(); $table->foreignId('parent_id')->nullable()->constrained('chart_accounts')->nullOnDelete(); $table->string('code')->unique(); $table->string('name'); $table->string('account_type')->index(); $table->string('normal_balance'); $table->boolean('is_cash_account')->default(false)->index(); $table->boolean('is_active')->default(true)->index(); $table->unsignedInteger('sequence')->default(0); $table->timestamps();
        });
        Schema::create('cash_accounts', function (Blueprint $table): void {
            $table->id(); $table->foreignId('chart_account_id')->constrained('chart_accounts')->restrictOnDelete(); $table->string('name'); $table->string('account_number')->nullable(); $table->string('bank_name')->nullable(); $table->decimal('opening_balance', 15, 2)->default(0); $table->decimal('current_balance', 15, 2)->default(0); $table->boolean('is_active')->default(true)->index(); $table->timestamps();
        });
        Schema::create('fee_types', function (Blueprint $table): void {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->string('category')->index(); $table->text('description')->nullable(); $table->decimal('default_amount', 15, 2)->nullable(); $table->string('billing_frequency')->index(); $table->boolean('is_mandatory')->default(true); $table->boolean('is_active')->default(true)->index(); $table->foreignId('revenue_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('billing_periods', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedTinyInteger('month')->nullable(); $table->unsignedSmallInteger('year'); $table->string('name'); $table->date('starts_on')->nullable(); $table->date('due_on')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); $table->unique(['academic_year_id', 'semester_id', 'month', 'year'], 'billing_period_unique');
        });
        Schema::create('student_invoices', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('student_enrollment_id')->constrained()->restrictOnDelete(); $table->foreignId('classroom_id')->constrained()->restrictOnDelete(); $table->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('billing_period_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('fee_type_id')->constrained()->restrictOnDelete(); $table->string('invoice_number')->unique(); $table->text('description')->nullable(); $table->decimal('original_amount', 15, 2); $table->decimal('discount_amount', 15, 2)->default(0); $table->decimal('penalty_amount', 15, 2)->default(0); $table->decimal('final_amount', 15, 2); $table->decimal('paid_amount', 15, 2)->default(0); $table->decimal('outstanding_amount', 15, 2); $table->date('due_on')->nullable()->index(); $table->string('status')->index(); $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['student_id', 'fee_type_id', 'billing_period_id'], 'student_invoice_unique');
        });
        Schema::create('student_discounts', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('fee_type_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete(); $table->string('discount_type'); $table->decimal('discount_value', 15, 2); $table->decimal('maximum_discount', 15, 2)->nullable(); $table->date('starts_on')->nullable(); $table->date('ends_on')->nullable(); $table->text('reason'); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('status')->index(); $table->timestamps();
        });
        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->id(); $table->string('transaction_number')->unique(); $table->date('transaction_date')->index(); $table->string('transaction_type')->index(); $table->text('description'); $table->string('reference_type')->nullable(); $table->unsignedBigInteger('reference_id')->nullable(); $table->string('status')->index(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('posted_at')->nullable(); $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('cancelled_at')->nullable(); $table->text('cancellation_reason')->nullable(); $table->timestamps(); $table->index(['reference_type', 'reference_id'], 'fin_ref_idx');
        });
        Schema::create('financial_transaction_lines', function (Blueprint $table): void {
            $table->id(); $table->foreignId('financial_transaction_id')->constrained()->cascadeOnDelete(); $table->foreignId('chart_account_id')->constrained('chart_accounts')->restrictOnDelete(); $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete(); $table->decimal('debit', 15, 2)->default(0); $table->decimal('credit', 15, 2)->default(0); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('student_payments', function (Blueprint $table): void {
            $table->id(); $table->string('payment_number')->unique(); $table->date('payment_date')->index(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('payment_method'); $table->string('reference_number')->nullable(); $table->decimal('total_amount', 15, 2); $table->string('status')->index(); $table->text('notes')->nullable(); $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('cancelled_at')->nullable(); $table->text('cancellation_reason')->nullable(); $table->foreignId('financial_transaction_id')->nullable()->constrained()->nullOnDelete(); $table->timestamps();
        });
        Schema::create('student_payment_allocations', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_payment_id')->constrained()->cascadeOnDelete(); $table->foreignId('student_invoice_id')->constrained()->restrictOnDelete(); $table->decimal('amount', 15, 2); $table->timestamps(); $table->unique(['student_payment_id', 'student_invoice_id'], 'pay_alloc_unique');
        });
        Schema::create('salary_components', function (Blueprint $table): void {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->string('component_type')->index(); $table->string('calculation_type'); $table->decimal('default_amount', 15, 2)->nullable(); $table->decimal('percentage', 8, 4)->nullable(); $table->boolean('taxable')->default(false); $table->boolean('is_attendance_based')->default(false); $table->boolean('is_active')->default(true)->index(); $table->foreignId('expense_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete(); $table->foreignId('payable_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('employee_salary_components', function (Blueprint $table): void {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->foreignId('salary_component_id')->constrained()->restrictOnDelete(); $table->decimal('amount', 15, 2)->nullable(); $table->decimal('percentage', 8, 4)->nullable(); $table->date('effective_from'); $table->date('effective_until')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->text('notes')->nullable(); $table->timestamps(); $table->index(['employee_id', 'salary_component_id'], 'emp_salary_component_idx');
        });
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->unsignedTinyInteger('month'); $table->unsignedSmallInteger('year'); $table->date('starts_on'); $table->date('ends_on'); $table->date('payment_date')->nullable(); $table->string('status')->index(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['month', 'year']);
        });
        Schema::create('employee_payrolls', function (Blueprint $table): void {
            $table->id(); $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete(); $table->foreignId('employee_id')->constrained()->restrictOnDelete(); $table->decimal('basic_salary', 15, 2)->default(0); $table->decimal('total_earnings', 15, 2)->default(0); $table->decimal('total_deductions', 15, 2)->default(0); $table->decimal('net_salary', 15, 2)->default(0); $table->unsignedSmallInteger('attendance_present')->default(0); $table->unsignedSmallInteger('attendance_late')->default(0); $table->unsignedSmallInteger('attendance_permission')->default(0); $table->unsignedSmallInteger('attendance_sick')->default(0); $table->unsignedSmallInteger('attendance_alpha')->default(0); $table->string('status')->index(); $table->timestamp('calculated_at')->nullable(); $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('reviewed_at')->nullable(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('approved_at')->nullable(); $table->timestamp('paid_at')->nullable(); $table->string('payment_method')->nullable(); $table->string('reference_number')->nullable(); $table->text('notes')->nullable(); $table->foreignId('financial_transaction_id')->nullable()->constrained()->nullOnDelete(); $table->timestamps(); $table->unique(['payroll_period_id', 'employee_id'], 'employee_payroll_unique');
        });
        Schema::create('employee_payroll_items', function (Blueprint $table): void {
            $table->id(); $table->foreignId('employee_payroll_id')->constrained()->cascadeOnDelete(); $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete(); $table->string('component_name_snapshot'); $table->string('component_type'); $table->decimal('quantity', 10, 2)->nullable(); $table->decimal('rate', 15, 2)->nullable(); $table->decimal('amount', 15, 2); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('document_sequences', function (Blueprint $table): void {
            $table->id(); $table->string('document_type'); $table->unsignedSmallInteger('year'); $table->unsignedTinyInteger('month'); $table->unsignedBigInteger('last_number')->default(0); $table->string('format'); $table->timestamps(); $table->unique(['document_type', 'year', 'month'], 'doc_sequence_unique');
        });
    }

    public function down(): void
    {
        foreach (['document_sequences','employee_payroll_items','employee_payrolls','payroll_periods','employee_salary_components','salary_components','student_payment_allocations','student_payments','financial_transaction_lines','financial_transactions','student_discounts','student_invoices','billing_periods','fee_types','cash_accounts','chart_accounts'] as $table) { Schema::dropIfExists($table); }
    }
};
