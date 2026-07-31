<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teaching_import_batches', function (Blueprint $table): void {
            $table->unsignedInteger('structure_load_count')->default(0);
            $table->unsignedInteger('assignment_candidate_count')->default(0);
            $table->unsignedInteger('additional_duty_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
        });
        Schema::table('teaching_import_rows', function (Blueprint $table): void {
            $table->dropUnique(['batch_id', 'sheet_name', 'row_number']);
            $table->unsignedInteger('source_sequence')->default(0)->after('row_number');
            $table->unique(['batch_id', 'sheet_name', 'row_number', 'source_sequence'], 'teaching_import_source_unique');
            $table->index(['batch_id', 'row_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('teaching_import_rows', function (Blueprint $table): void {
            $table->dropIndex(['batch_id', 'row_type', 'status']);
            $table->dropUnique('teaching_import_source_unique');
            $table->dropColumn('source_sequence');
            $table->unique(['batch_id', 'sheet_name', 'row_number']);
        });
        Schema::table('teaching_import_batches', fn (Blueprint $table) => $table->dropColumn(['structure_load_count', 'assignment_candidate_count', 'additional_duty_count', 'conflict_count']));
    }
};
