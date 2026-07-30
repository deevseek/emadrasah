<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SchoolProfile;
use Illuminate\Database\Seeder;

class SchoolProfileSeeder extends Seeder
{
    public function run(): void
    {
        SchoolProfile::query()->firstOrCreate([], [
            'name' => config('emadrasah.name'), 'short_name' => config('emadrasah.short_name'),
            'education_level' => config('emadrasah.level'), 'status' => config('emadrasah.status'),
            'nsm' => config('emadrasah.nsm'), 'npsn' => config('emadrasah.npsn'),
        ]);
    }
}
