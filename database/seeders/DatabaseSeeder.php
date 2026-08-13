<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AccessControlSeeder::class);
        $this->call(ApplicationSettingSeeder::class);
        $this->call(SchoolProfileSeeder::class);
        $this->call(AcademicPeriodSeeder::class);
        $this->call(GradeLevelSeeder::class);
    }
}
