<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incoming_emails', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_message_id')->unique();
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->json('to_addresses');
            $table->json('cc_addresses')->nullable();
            $table->string('subject')->default('(Tanpa subjek)');
            $table->longText('body');
            $table->json('attachments')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_emails');
    }
};
