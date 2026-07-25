<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_schedules', function (Blueprint $table): void {
            $table->string('shared_session_code', 100)->nullable()->after('activity_name')->index();
            $table->string('shared_session_name', 190)->nullable()->after('shared_session_code');
            $table->index(['semester_id', 'day_of_week', 'starts_at', 'ends_at', 'shared_session_code'], 'lesson_schedules_shared_session_slot_index');
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->unsignedInteger('updated_rows')->default(0)->after('imported_rows');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_schedules', function (Blueprint $table): void {
            $table->dropIndex('lesson_schedules_shared_session_slot_index');
            $table->dropIndex(['shared_session_code']);
            $table->dropColumn(['shared_session_code', 'shared_session_name']);
        });
        Schema::table('import_batches', fn (Blueprint $table) => $table->dropColumn('updated_rows'));
    }
};
