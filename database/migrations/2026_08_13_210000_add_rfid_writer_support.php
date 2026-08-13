<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_rfid_cards', function (Blueprint $table): void {
            $table->char('card_token', 32)->nullable()->unique()->after('uid');
            $table->foreignId('issued_by')->nullable()->after('last_used_at')->constrained('users')->nullOnDelete();
        });
        Schema::table('rfid_devices', function (Blueprint $table): void {
            $table->string('firmware_version')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->string('mode', 30)->nullable()->index();
        });
        Schema::create('rfid_device_commands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_id')->constrained('rfid_devices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('command', 50)->index();
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('replaces_existing')->default(false);
            $table->timestamp('expires_at')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_device_commands');
        Schema::table('rfid_devices', fn (Blueprint $table) => $table->dropColumn(['firmware_version', 'ip_address', 'rssi', 'mode']));
        Schema::table('student_rfid_cards', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropUnique(['card_token']);
            $table->dropColumn('card_token');
        });
    }
};
