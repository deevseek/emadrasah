<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SemesterType;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $name = (string) env('SEED_ACADEMIC_YEAR', '');
        if ($name === '') return;
        DB::transaction(function () use ($name): void {
            $year = AcademicYear::query()->firstOrCreate(['name' => $name], ['starts_at' => env('SEED_ACADEMIC_YEAR_START'), 'ends_at' => env('SEED_ACADEMIC_YEAR_END'), 'is_active' => false]);
            $odd = $year->semesters()->firstOrCreate(['type' => SemesterType::Ganjil->value], ['name' => SemesterType::Ganjil->label(), 'starts_at' => env('SEED_ODD_SEMESTER_START'), 'ends_at' => env('SEED_ODD_SEMESTER_END'), 'is_active' => false]);
            $year->semesters()->firstOrCreate(['type' => SemesterType::Genap->value], ['name' => SemesterType::Genap->label(), 'starts_at' => env('SEED_EVEN_SEMESTER_START'), 'ends_at' => env('SEED_EVEN_SEMESTER_END'), 'is_active' => false]);
            if (filter_var(env('SEED_ACADEMIC_PERIOD_ACTIVE', false), FILTER_VALIDATE_BOOL)) {
                AcademicYear::query()->update(['is_active' => false]); $year->update(['is_active' => true]);
                $year->semesters()->update(['is_active' => false]); $odd->update(['is_active' => true]);
            }
        });
    }
}
