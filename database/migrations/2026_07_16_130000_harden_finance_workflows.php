<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('financial_transactions', 'reversal_transaction_id')) {
                $table->foreignId('reversal_transaction_id')->nullable()->after('reference_id')->constrained('financial_transactions')->nullOnDelete();
            }
        });
        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->index(['student_id', 'fee_type_id', 'academic_year_id', 'semester_id', 'billing_period_id'], 'student_invoice_report_idx');
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('financial_transactions', 'reversal_transaction_id')) {
                $table->dropConstrainedForeignId('reversal_transaction_id');
            }
        });
        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->dropIndex('student_invoice_report_idx');
        });
    }
};
