<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
            // Existing snapshots remain historical and are never silently reconciled.
            $table->softDeletes();
            $table->string('origin', 20)->default('legacy')->index();
            $table->string('daily_source', 20)->nullable();
            $table->dateTime('arrival_at')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('corrected_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('corrected_by');
            $table->dropIndex(['origin']);
            $table->dropSoftDeletes();
            $table->dropColumn(['origin', 'daily_source', 'arrival_at', 'corrected_at']);
        });
    }
};
