<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personnel_attendance_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->char('device_uuid_hash', 64);
            $table->string('device_name')->nullable(); $table->string('browser')->nullable(); $table->string('platform')->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamp('first_seen_at'); $table->timestamp('last_seen_at');
            $table->boolean('is_trusted')->default(false); $table->timestamp('trusted_at')->nullable();
            $table->foreignId('trusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable(); $table->timestamps();
            $table->unique(['personnel_id', 'device_uuid_hash'], 'personnel_device_unique');
        });
        Schema::create('attendance_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->char('nonce_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->char('session_hash', 64); $table->char('device_uuid_hash', 64)->nullable();
            $table->string('intended_action', 16); $table->timestamp('expires_at'); $table->timestamp('used_at')->nullable(); $table->timestamps();
        });
        Schema::create('attendance_face_verifications', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->foreignUuid('challenge_id')->constrained('attendance_challenges')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->string('provider'); $table->decimal('confidence', 8, 4)->nullable();
            $table->boolean('liveness_passed')->nullable(); $table->timestamp('verified_at'); $table->timestamp('expires_at'); $table->timestamps();
        });
        Schema::create('personnel_attendance_audits', function (Blueprint $table): void {
            $table->id(); $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('personnel_attendances')->nullOnDelete();
            $table->foreignUuid('challenge_id')->nullable()->constrained('attendance_challenges')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('personnel_attendance_devices')->nullOnDelete();
            $table->string('event'); $table->string('result'); $table->ipAddress('ip')->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable(); $table->decimal('distance', 10, 2)->nullable();
            $table->boolean('face_verified')->default(false); $table->decimal('face_confidence', 8, 4)->nullable();
            $table->json('risk_flags')->nullable(); $table->timestamp('occurred_at'); $table->timestamps();
        });
        Schema::table('personnel_attendances', function (Blueprint $table): void {
            $table->decimal('check_in_accuracy', 8, 2)->nullable(); $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->decimal('check_in_distance', 10, 2)->nullable(); $table->decimal('check_out_distance', 10, 2)->nullable();
            $table->ipAddress('check_in_ip')->nullable(); $table->ipAddress('check_out_ip')->nullable();
            $table->foreignId('check_in_device_id')->nullable()->constrained('personnel_attendance_devices')->nullOnDelete();
            $table->foreignId('check_out_device_id')->nullable()->constrained('personnel_attendance_devices')->nullOnDelete();
            $table->unsignedTinyInteger('risk_score')->default(0); $table->json('risk_flags')->nullable();
            $table->boolean('requires_review')->default(false)->index(); $table->string('review_status')->nullable();
            $table->text('manual_reason')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('personnel_attendances', fn (Blueprint $table) => $table->dropConstrainedForeignId('check_out_device_id'));
        Schema::table('personnel_attendances', fn (Blueprint $table) => $table->dropConstrainedForeignId('check_in_device_id'));
        Schema::table('personnel_attendances', fn (Blueprint $table) => $table->dropColumn(['check_in_accuracy','check_out_accuracy','check_in_distance','check_out_distance','check_in_ip','check_out_ip','risk_score','risk_flags','requires_review','review_status','manual_reason']));
        Schema::dropIfExists('personnel_attendance_audits'); Schema::dropIfExists('attendance_face_verifications'); Schema::dropIfExists('attendance_challenges'); Schema::dropIfExists('personnel_attendance_devices');
    }
};
