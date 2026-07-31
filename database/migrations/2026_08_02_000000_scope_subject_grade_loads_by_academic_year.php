<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subject_grade_loads', function (Blueprint $table): void {
            $table->dropUnique('subject_grade_program_unique');
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
            $table->unique(['subject_id', 'grade_level_id', 'program_type'], 'subject_grade_program_unique');
        });
    }
};
