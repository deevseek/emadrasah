<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_journal_attachments')) {
            return;
        }
        Schema::create('teaching_journal_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teaching_journal_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('drive_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_journal_attachments');
    }
};
