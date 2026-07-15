<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SchoolProfile;
use Illuminate\Database\Seeder;

class SchoolProfileSeeder extends Seeder
{
    public function run(): void
    {
        SchoolProfile::firstOrCreate(['id' => 1], ['school_name' => 'MI Muslimat NU', 'foundation_name' => 'Yayasan Muslimat NU', 'timezone' => 'Asia/Jakarta']);
    }
}
