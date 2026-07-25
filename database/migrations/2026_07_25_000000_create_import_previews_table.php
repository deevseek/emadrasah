<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_previews', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('type', 50)->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->string('payload_path');
            $table->unsignedInteger('row_count')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_previews');
    }
};
