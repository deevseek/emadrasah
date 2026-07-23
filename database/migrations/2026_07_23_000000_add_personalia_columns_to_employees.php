<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('rank_grade')->nullable()->after('position');
            $table->string('peg_id')->nullable()->after('rank_grade');
            $table->string('certification_status')->nullable()->after('peg_id');
            $table->string('certification_subject')->nullable()->after('certification_status');
            $table->unsignedSmallInteger('weekly_teaching_hours')->nullable()->after('certification_subject');
            $table->string('bank_name')->nullable()->after('weekly_teaching_hours');
            $table->string('bank_account_number')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn(['rank_grade', 'peg_id', 'certification_status', 'certification_subject', 'weekly_teaching_hours', 'bank_name', 'bank_account_number']);
        });
    }
};
