<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_rfid_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('uid', 64)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
        Schema::create('rfid_devices', function (Blueprint $table): void {
            $table->id(); $table->string('device_id')->unique(); $table->string('name');
            $table->string('token_hash', 64)->unique(); $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable(); $table->timestamps();
        });
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->string('source', 20)->default('manual')->after('status')->index();
            $table->timestamp('scanned_at')->nullable()->after('source');
            $table->foreignId('rfid_device_id')->nullable()->after('scanned_at')->constrained('rfid_devices')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->change();
        });
    }
    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rfid_device_id'); $table->dropColumn(['source', 'scanned_at']);
        });
        Schema::dropIfExists('rfid_devices'); Schema::dropIfExists('student_rfid_cards');
    }
};
