<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, InventoryPermissionSeeder::class, InventoryMasterSeeder::class, FinanceModuleSeeder::class, SchoolProfileSeeder::class, AcademicPeriodSeeder::class, SettingSeeder::class, WorkScheduleSeeder::class,
            StudentAffairsSeeder::class,
            OfficialLessonScheduleSeeder::class, BtaqAssessmentReportSeeder::class, SuperAdminSeeder::class]);
    }
}
