<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_schemes', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->cascadeOnUpdate(); $table->foreignId('semester_id')->constrained()->cascadeOnUpdate();
            $table->string('name'); $table->string('code'); $table->text('description')->nullable(); $table->string('calculation_method')->default('weighted_average'); $table->unsignedTinyInteger('decimal_precision')->default(2); $table->string('rounding_method')->default('nearest'); $table->boolean('is_active')->default(false); $table->date('effective_start_date')->nullable(); $table->date('effective_end_date')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['academic_year_id','semester_id','code']);
        });
        Schema::create('subject_minimum_criteria', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->cascadeOnUpdate(); $table->foreignId('semester_id')->constrained()->cascadeOnUpdate(); $table->foreignId('subject_id')->constrained()->cascadeOnUpdate(); $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete(); $table->string('grade_level')->nullable(); $table->decimal('minimum_score',5,2); $table->date('effective_start_date')->nullable(); $table->date('effective_end_date')->nullable(); $table->boolean('is_active')->default(true); $table->text('notes')->nullable(); $table->timestamps(); $table->index(['academic_year_id','semester_id','subject_id','classroom_id','grade_level'],'smc_lookup');
        });
        Schema::create('assessment_periods', function (Blueprint $table): void {
            $table->id(); $table->foreignId('academic_year_id')->constrained()->cascadeOnUpdate(); $table->foreignId('semester_id')->constrained()->cascadeOnUpdate(); $table->string('name'); $table->dateTime('start_at'); $table->dateTime('end_at'); $table->string('status')->default('draft'); $table->boolean('allow_draft')->default(true); $table->boolean('allow_submission')->default(false); $table->boolean('allow_revision')->default(false); $table->timestamp('locked_at')->nullable(); $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('notes')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_periods'); Schema::dropIfExists('subject_minimum_criteria'); Schema::dropIfExists('assessment_schemes');
    }
};
