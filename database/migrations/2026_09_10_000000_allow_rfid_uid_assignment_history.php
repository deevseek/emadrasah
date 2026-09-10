<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_rfid_cards', function (Blueprint $table): void {
            $table->dropUnique(['uid']);
            $table->index('uid');
        });
    }

    public function down(): void
    {
        Schema::table('student_rfid_cards', function (Blueprint $table): void {
            $table->dropIndex(['uid']);
            $table->unique('uid');
        });
    }
};
