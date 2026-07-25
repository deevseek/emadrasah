<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('original_filename');
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->foreignId('import_batch_id')->nullable()->after('replaced_by_id')->constrained('import_batches')->nullOnDelete();
            $table->string('source_reference')->nullable()->after('import_batch_id');
        });
        Schema::table('lesson_schedules', function (Blueprint $table): void {
            $table->string('entry_type', 20)->default('lesson')->after('teaching_assignment_id')->index();
            $table->string('activity_name')->nullable()->after('entry_type');
            $table->boolean('counts_as_teaching_hour')->default(true)->after('lesson_hours');
            $table->string('source_reference')->nullable()->after('notes');
            $table->foreignId('import_batch_id')->nullable()->after('source_reference')->constrained('import_batches')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_schedules', function (Blueprint $table): void { $table->dropForeign(['import_batch_id']); $table->dropColumn(['entry_type','activity_name','counts_as_teaching_hour','source_reference','import_batch_id']); });
        Schema::table('teaching_assignments', function (Blueprint $table): void { $table->dropForeign(['import_batch_id']); $table->dropColumn(['import_batch_id','source_reference']); });
        Schema::dropIfExists('import_batches');
    }
};
