<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const BATCH_INDEX = 'teaching_import_rows_batch_id_index';

    private const LEGACY_UNIQUE = 'teaching_import_rows_batch_id_sheet_name_row_number_unique';

    private const SOURCE_UNIQUE = 'teaching_import_source_unique';

    private const TYPE_STATUS_INDEX = 'teaching_import_rows_batch_id_row_type_status_index';

    public function up(): void
    {
        $this->addBatchColumns();

        if (! Schema::hasColumn('teaching_import_rows', 'source_sequence')) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->unsignedInteger('source_sequence')->default(0)->after('row_number');
            });
        }

        // MySQL memerlukan index pengganti untuk foreign key batch_id sebelum
        // unique index lama boleh dilepas. Operasi ini sengaja dipisahkan.
        if (! Schema::hasIndex('teaching_import_rows', self::BATCH_INDEX)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->index('batch_id', self::BATCH_INDEX);
            });
        }

        if (Schema::hasIndex('teaching_import_rows', self::LEGACY_UNIQUE)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->dropUnique(self::LEGACY_UNIQUE);
            });
        }

        if (! Schema::hasIndex('teaching_import_rows', self::SOURCE_UNIQUE)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->unique(['batch_id', 'sheet_name', 'row_number', 'source_sequence'], self::SOURCE_UNIQUE);
            });
        }

        if (! Schema::hasIndex('teaching_import_rows', self::TYPE_STATUS_INDEX)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->index(['batch_id', 'row_type', 'status'], self::TYPE_STATUS_INDEX);
            });
        }
    }

    public function down(): void
    {
        // Pastikan foreign key selalu mempunyai index pendukung selama rollback.
        if (! Schema::hasIndex('teaching_import_rows', self::BATCH_INDEX)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->index('batch_id', self::BATCH_INDEX);
            });
        }

        if (Schema::hasIndex('teaching_import_rows', self::SOURCE_UNIQUE)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->dropUnique(self::SOURCE_UNIQUE);
            });
        }

        if (! Schema::hasIndex('teaching_import_rows', self::LEGACY_UNIQUE)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->unique(['batch_id', 'sheet_name', 'row_number'], self::LEGACY_UNIQUE);
            });
        }

        if (Schema::hasIndex('teaching_import_rows', self::TYPE_STATUS_INDEX)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->dropIndex(self::TYPE_STATUS_INDEX);
            });
        }

        if (Schema::hasColumn('teaching_import_rows', 'source_sequence')) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->dropColumn('source_sequence');
            });
        }

        // Unique index lama kembali dapat menopang foreign key seperti schema awal.
        if (Schema::hasIndex('teaching_import_rows', self::BATCH_INDEX)) {
            Schema::table('teaching_import_rows', function (Blueprint $table): void {
                $table->dropIndex(self::BATCH_INDEX);
            });
        }

        foreach (['structure_load_count', 'assignment_candidate_count', 'additional_duty_count', 'conflict_count'] as $column) {
            if (Schema::hasColumn('teaching_import_batches', $column)) {
                Schema::table('teaching_import_batches', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    private function addBatchColumns(): void
    {
        foreach (['structure_load_count', 'assignment_candidate_count', 'additional_duty_count', 'conflict_count'] as $column) {
            if (! Schema::hasColumn('teaching_import_batches', $column)) {
                Schema::table('teaching_import_batches', function (Blueprint $table) use ($column): void {
                    $table->unsignedInteger($column)->default(0);
                });
            }
        }
    }
};
