<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teaching_journal_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teaching_journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
            $table->unique(['teaching_journal_id', 'student_id']);
        });
        Schema::create('teaching_journal_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('original_name');
            $table->string('path');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('teaching_journal_templates');
        Schema::dropIfExists('teaching_journal_attendances');
    }
};
