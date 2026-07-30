<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\GradeLevel;
use Illuminate\Database\Seeder;
class GradeLevelSeeder extends Seeder { public function run():void { foreach(['I','II','III','IV','V','VI'] as $i=>$roman) GradeLevel::updateOrCreate(['number'=>$i+1],['name'=>'Kelas '.($i+1),'roman_label'=>$roman,'sort_order'=>$i+1,'is_active'=>true]); } }
