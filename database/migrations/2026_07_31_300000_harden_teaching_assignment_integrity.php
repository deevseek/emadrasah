<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teaching_assignment_sets', function (Blueprint $table): void {
            $table->unsignedBigInteger('active_academic_year_guard')->nullable()->after('status')->unique();
        });
        $activeSets = DB::table('teaching_assignment_sets')->where('status', 'active')->orderByDesc('activated_at')->orderByDesc('id')->get()->groupBy('academic_year_id');
        foreach ($activeSets as $academicYearId => $sets) {
            DB::table('teaching_assignment_sets')->where('id', $sets->first()->id)->update(['active_academic_year_guard' => $academicYearId]);
            if ($sets->count() > 1) DB::table('teaching_assignment_sets')->whereIn('id', $sets->skip(1)->pluck('id'))->update(['status' => 'archived']);
        }
    }
    public function down(): void
    {
        Schema::table('teaching_assignment_sets', fn (Blueprint $table) => $table->dropUnique(['active_academic_year_guard']));
        Schema::table('teaching_assignment_sets', fn (Blueprint $table) => $table->dropColumn('active_academic_year_guard'));
    }
};
