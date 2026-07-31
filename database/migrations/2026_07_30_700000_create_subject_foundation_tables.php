<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->string('program_type', 20)->default('regular')->after('grade_level_id')->index();
        });
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name', 150); $table->string('category', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0); $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('subject_grade_loads', function (Blueprint $table): void {
            $table->id(); $table->foreignId('subject_id')->constrained()->cascadeOnDelete(); $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->string('program_type', 20)->default('regular'); $table->unsignedTinyInteger('weekly_hours'); $table->timestamps();
            $table->unique(['subject_id', 'grade_level_id', 'program_type'], 'subject_grade_program_unique'); $table->index(['grade_level_id', 'program_type']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('subject_grade_loads'); Schema::dropIfExists('subjects');
        Schema::table('classrooms', fn (Blueprint $table) => $table->dropColumn('program_type'));
    }
};
