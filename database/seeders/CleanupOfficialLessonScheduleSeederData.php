<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LessonSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CleanupOfficialLessonScheduleSeederData extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            LessonSchedule::query()
                ->where('notes', 'like', 'Diimpor dari jadwal resmi MI Muslimat NU Demak TA 2026/2027 semester ganjil%')
                ->delete();
        });
    }
}
