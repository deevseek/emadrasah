<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_face_verifications', function (Blueprint $table): void {
            $table->string('snapshot_path')->nullable()->after('liveness_passed');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_face_verifications', function (Blueprint $table): void {
            $table->dropColumn('snapshot_path');
        });
    }
};
