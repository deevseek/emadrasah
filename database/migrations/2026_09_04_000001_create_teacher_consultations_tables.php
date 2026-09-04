<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_consultations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guardian_id')->constrained('guardian_profiles')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['guardian_id', 'student_id', 'classroom_id']);
            $table->index(['teacher_user_id', 'last_message_at']);
        });

        Schema::create('teacher_consultation_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_consultation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['teacher_consultation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_consultation_messages');
        Schema::dropIfExists('teacher_consultations');
    }
};
