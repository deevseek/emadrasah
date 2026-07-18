<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('fee_types', 'is_recurring')) { $table->boolean('is_recurring')->default(false)->after('is_mandatory'); }
            if (! Schema::hasColumn('fee_types', 'allow_partial_payment')) { $table->boolean('allow_partial_payment')->default(true)->after('is_active'); }
            if (! Schema::hasColumn('fee_types', 'allow_discount')) { $table->boolean('allow_discount')->default(true)->after('allow_partial_payment'); }
            if (! Schema::hasColumn('fee_types', 'sort_order')) { $table->unsignedInteger('sort_order')->default(0)->after('allow_discount'); }
            if (! Schema::hasColumn('fee_types', 'notes')) { $table->text('notes')->nullable()->after('sort_order'); }
        });
        Schema::table('student_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_invoices', 'title')) { $table->string('title')->nullable()->after('invoice_number'); }
            if (! Schema::hasColumn('student_invoices', 'billing_month')) { $table->unsignedTinyInteger('billing_month')->nullable()->after('billing_period_id'); }
            if (! Schema::hasColumn('student_invoices', 'billing_year')) { $table->unsignedSmallInteger('billing_year')->nullable()->after('billing_month'); }
            if (! Schema::hasColumn('student_invoices', 'cancelled_by')) { $table->foreignId('cancelled_by')->nullable()->after('generated_by')->constrained('users')->nullOnDelete(); }
            if (! Schema::hasColumn('student_invoices', 'cancelled_at')) { $table->timestamp('cancelled_at')->nullable()->after('cancelled_by'); }
            if (! Schema::hasColumn('student_invoices', 'cancellation_reason')) { $table->text('cancellation_reason')->nullable()->after('cancelled_at'); }
        });
        Schema::table('student_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_payments', 'receipt_number')) { $table->string('receipt_number')->nullable()->unique()->after('payment_number'); }
            if (! Schema::hasColumn('student_payments', 'payer_name')) { $table->string('payer_name')->nullable()->after('reference_number'); }
        });
        Schema::table('student_discounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_discounts', 'student_invoice_id')) { $table->foreignId('student_invoice_id')->nullable()->after('id')->constrained('student_invoices')->nullOnDelete(); }
            if (! Schema::hasColumn('student_discounts', 'applied_by')) { $table->foreignId('applied_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete(); }
            if (! Schema::hasColumn('student_discounts', 'cancelled_by')) { $table->foreignId('cancelled_by')->nullable()->after('applied_by')->constrained('users')->nullOnDelete(); }
            if (! Schema::hasColumn('student_discounts', 'cancelled_at')) { $table->timestamp('cancelled_at')->nullable()->after('cancelled_by'); }
            if (! Schema::hasColumn('student_discounts', 'cancellation_reason')) { $table->text('cancellation_reason')->nullable()->after('cancelled_at'); }
        });
    }
};
