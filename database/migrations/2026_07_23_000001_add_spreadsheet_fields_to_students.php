<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('disability')->nullable()->after('special_needs');
            $table->string('kip_pip_number')->nullable()->after('bpjs_number');
        });
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn(['disability', 'kip_pip_number']));
    }
};
