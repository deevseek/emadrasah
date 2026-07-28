<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, InventoryPermissionSeeder::class, InventoryMasterSeeder::class, FinanceModuleSeeder::class, SchoolProfileSeeder::class, AcademicPeriodSeeder::class, MimnuAcademicMasterSeeder::class, MimnuSubjectSeeder::class, MimnuTeachingAssignmentSeeder::class, SettingSeeder::class, WorkScheduleSeeder::class,
            StudentAffairsSeeder::class,
            BtaqAssessmentReportSeeder::class, SuperAdminSeeder::class]);
    }
}
