<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pmbm_settings', function (Blueprint $table): void {
            $table->text('admission_requirements')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pmbm_settings', function (Blueprint $table): void {
            $table->dropColumn('admission_requirements');
        });
    }
};
