<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('pmbm_settings', function (Blueprint $table): void { $table->json('schedule_items')->nullable()->after('fee_items'); }); } public function down(): void { Schema::table('pmbm_settings', function (Blueprint $table): void { $table->dropColumn('schedule_items'); }); } };
