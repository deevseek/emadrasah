<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_attendance_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rfid_device_id')->constrained('rfid_devices')->cascadeOnDelete();
            $requestId = $table->string('request_id', 128);
            if (DB::getDriverName() === 'mysql') {
                $requestId->collation('utf8mb4_bin');
            }
            $table->char('payload_hash', 64);
            // Metadata perangkat saja, tidak dipakai menentukan tanggal presensi.
            $table->string('device_scanned_at', 64)->nullable();
            $table->json('response');
            $table->timestamps();
            $table->unique(['rfid_device_id', 'request_id'], 'rfid_attendance_request_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_attendance_requests');
    }
};
