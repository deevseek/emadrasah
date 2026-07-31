<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const SUBJECT_FOREIGN_KEY_INDEX = 'subject_grade_loads_subject_fk_index';

    public function up(): void
    {
        // MySQL may use the old composite unique index to enforce the subject_id
        // foreign key. Give that foreign key a dedicated supporting index before
        // removing the old unique constraint (otherwise MySQL raises error 1553).
        if (! Schema::hasIndex('subject_grade_loads', self::SUBJECT_FOREIGN_KEY_INDEX)) {
            Schema::table('subject_grade_loads', function (Blueprint $table): void {
                $table->index('subject_id', self::SUBJECT_FOREIGN_KEY_INDEX);
            });
        }

        Schema::table('subject_grade_loads', function (Blueprint $table): void {
            $table->dropUnique('subject_grade_program_unique');
        });

        Schema::table('subject_grade_loads', function (Blueprint $table): void {
            $table->foreignId('academic_year_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->unique(
                ['academic_year_id', 'subject_id', 'grade_level_id', 'program_type'],
                'subject_year_grade_program_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('subject_grade_loads', function (Blueprint $table): void {
            $table->dropUnique('subject_year_grade_program_unique');
            $table->dropConstrainedForeignId('academic_year_id');
        });

        Schema::table('subject_grade_loads', function (Blueprint $table): void {
            $table->unique(['subject_id', 'grade_level_id', 'program_type'], 'subject_grade_program_unique');
        });

        if (Schema::hasIndex('subject_grade_loads', self::SUBJECT_FOREIGN_KEY_INDEX)) {
            Schema::table('subject_grade_loads', function (Blueprint $table): void {
                $table->dropIndex(self::SUBJECT_FOREIGN_KEY_INDEX);
            });
        }
    }
};
