<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL commits DDL implicitly. A failed deployment can therefore leave
        // only some of these columns behind while the migration remains pending.
        if (! Schema::hasColumn('teaching_journal_attendances', 'source')) {
            Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
                $table->string('source', 20)->default('legacy')->after('status')->index();
            });
        }
        if (! Schema::hasColumn('teaching_journal_attendances', 'correction_reason')) {
            Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
                $table->text('correction_reason')->nullable()->after('notes');
            });
        }
        if (! Schema::hasColumn('teaching_journal_attendances', 'corrected_by')) {
            Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
                $table->foreignId('corrected_by')->nullable()->after('correction_reason')->constrained('users')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('teaching_journal_attendances', 'corrected_at')) {
            Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
                $table->timestamp('corrected_at')->nullable()->after('corrected_by');
            });
        }
    }

    public function down(): void
    {
        $foreignKey = collect(Schema::getForeignKeys('teaching_journal_attendances'))
            ->first(fn (array $key): bool => in_array('corrected_by', $key['columns'] ?? [], true));
        if ($foreignKey) {
            Schema::table('teaching_journal_attendances', fn (Blueprint $table) => $table->dropForeign($foreignKey['name']));
        }
        foreach (['corrected_at', 'corrected_by', 'correction_reason', 'source'] as $column) {
            if (Schema::hasColumn('teaching_journal_attendances', $column)) {
                Schema::table('teaching_journal_attendances', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
