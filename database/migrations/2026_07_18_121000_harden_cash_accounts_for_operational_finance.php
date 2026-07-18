<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('cash_accounts', 'code')) { $table->string('code')->nullable()->unique()->after('id'); }
            if (! Schema::hasColumn('cash_accounts', 'account_type')) { $table->string('account_type')->default('cash')->after('name'); }
            if (! Schema::hasColumn('cash_accounts', 'institution_name')) { $table->string('institution_name')->nullable()->after('account_type'); }
            if (! Schema::hasColumn('cash_accounts', 'account_holder')) { $table->string('account_holder')->nullable()->after('account_number'); }
            if (! Schema::hasColumn('cash_accounts', 'opening_balance_date')) { $table->date('opening_balance_date')->nullable()->after('opening_balance'); }
            if (! Schema::hasColumn('cash_accounts', 'allow_negative_balance')) { $table->boolean('allow_negative_balance')->default(false)->after('current_balance'); }
            if (! Schema::hasColumn('cash_accounts', 'is_default')) { $table->boolean('is_default')->default(false)->after('is_active'); }
            if (! Schema::hasColumn('cash_accounts', 'notes')) { $table->text('notes')->nullable()->after('is_default'); }
            if (! Schema::hasColumn('cash_accounts', 'created_by')) { $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete(); }
            if (! Schema::hasColumn('cash_accounts', 'updated_by')) { $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete(); }
        });
    }
    public function down(): void {}
};
