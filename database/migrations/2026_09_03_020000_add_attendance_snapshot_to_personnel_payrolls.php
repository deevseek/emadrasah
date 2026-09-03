<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personnel_payrolls', function (Blueprint $table): void {
            $table->string('calculation_method')->default('monthly')->after('daily_salary');
            $table->json('attendance_summary')->nullable()->after('calculation_method');
        });
    }

    public function down(): void
    {
        Schema::table('personnel_payrolls', fn (Blueprint $table) => $table->dropColumn(['calculation_method', 'attendance_summary']));
    }
};
