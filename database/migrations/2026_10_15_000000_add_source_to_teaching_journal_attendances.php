<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
            $table->string('source', 20)->default('legacy')->after('status')->index();
            $table->text('correction_reason')->nullable()->after('notes');
            $table->foreignId('corrected_by')->nullable()->after('correction_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at')->nullable()->after('corrected_by');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('corrected_by');
            $table->dropColumn(['source', 'correction_reason', 'corrected_at']);
        });
    }
};
