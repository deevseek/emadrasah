<?php

declare(strict_types=1);

use App\Services\Foundation\AcademicPeriodService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $activeSemesters = DB::table('semesters')->where('is_active', true)->get();

            // Recover the selected year only when the active semester is unambiguous.
            if ($activeSemesters->count() !== 1) {
                return;
            }

            $yearId = $activeSemesters->sole()->academic_year_id;
            DB::table('academic_years')->where('id', '!=', $yearId)->where('is_active', true)->update(['is_active' => false]);
            DB::table('academic_years')->where('id', $yearId)->update(['is_active' => true]);
        });

        Cache::forget(AcademicPeriodService::CACHE_KEY);
    }

    public function down(): void
    {
        // Preserve the corrected selection when rolling back.
    }
};
