<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table): void {
            $table->text('topic')->change();
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table): void {
            $table->string('topic', 255)->change();
        });
    }
};
