<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teaching_assignment_sets', function (Blueprint $table): void {
            $table->foreignId('teaching_import_batch_id')->nullable()->after('academic_year_id')->constrained()->nullOnDelete();
        });
        Schema::create('teaching_assignment_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_set_id')->constrained('teaching_assignment_sets')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['assignment_set_id', 'classroom_id', 'subject_id'], 'assignment_exception_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('teaching_assignment_exceptions');
        Schema::table('teaching_assignment_sets', fn (Blueprint $table) => $table->dropConstrainedForeignId('teaching_import_batch_id'));
    }
};
