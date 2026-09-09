<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_face_verifications', function (Blueprint $table): void {
            $table->unsignedTinyInteger('valid_frames')->nullable()->after('confidence');
            $table->unsignedTinyInteger('matched_frames')->nullable()->after('valid_frames');
            $table->json('confidence_summary')->nullable()->after('matched_frames');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_face_verifications', fn (Blueprint $table) => $table->dropColumn(['valid_frames', 'matched_frames', 'confidence_summary']));
    }
};
