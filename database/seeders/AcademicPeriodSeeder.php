<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class AcademicPeriodSeeder extends Seeder { public function run(): void { $year=AcademicYear::firstOrCreate(['name'=>'2026/2027'], ['starts_on'=>'2026-07-01','ends_on'=>'2027-06-30','is_active'=>true]); Semester::firstOrCreate(['academic_year_id'=>$year->id,'term'=>1], ['name'=>'Ganjil','starts_on'=>'2026-07-01','ends_on'=>'2026-12-31','is_active'=>true]); Semester::firstOrCreate(['academic_year_id'=>$year->id,'term'=>2], ['name'=>'Genap','starts_on'=>'2027-01-01','ends_on'=>'2027-06-30','is_active'=>false]); } }
