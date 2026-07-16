<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BtaqLevel;
use App\Models\BtaqMaterial;
use App\Models\Extracurricular;
use App\Models\PredicateRange;
use Illuminate\Database\Seeder;

class BtaqAssessmentReportSeeder extends Seeder
{
    public function run(): void
    {
        $levels = ['Pra Iqra','Iqra 1','Iqra 2','Iqra 3','Iqra 4','Iqra 5','Iqra 6','Al-Qur’an','Tahsin','Tahfidz'];
        foreach ($levels as $i => $name) {
            $level = BtaqLevel::updateOrCreate(['code' => 'BTAQ-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)], ['name' => $name, 'sequence' => $i + 1, 'is_active' => true]);
            BtaqMaterial::updateOrCreate(['code' => $level->code.'-READ'], ['btaq_level_id' => $level->id, 'name' => 'Bacaan '.$name, 'category' => 'reading', 'sequence' => 1, 'target_description' => 'Target membaca '.$name, 'is_active' => true]);
        }
        foreach ([['A','Sangat Baik',90,100,1],['B','Baik',80,89.99,2],['C','Cukup',70,79.99,3],['D','Perlu Bimbingan',0,69.99,4]] as [$code,$label,$min,$max,$seq]) {
            PredicateRange::updateOrCreate(['code'=>$code], ['label'=>$label,'minimum_score'=>$min,'maximum_score'=>$max,'sequence'=>$seq,'is_active'=>true,'description_template'=>'Ananda menunjukkan capaian '.$label.'.']);
        }
        foreach ([['PRM','Pramuka'],['QIR','Qiraah'],['KLG','Kaligrafi']] as [$code,$name]) {
            Extracurricular::updateOrCreate(['code'=>$code], ['name'=>$name,'is_active'=>true]);
        }
    }
}
