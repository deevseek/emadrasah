<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

final class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $days = ['senin','selasa','rabu','kamis','jumat','sabtu'];
        foreach ([['Guru/Pegawai Reguler','regular','07:00','13:30'],['Guru BTAQ','btaq','07:00','10:30'],['Program Full Day','full_day','07:00','15:00']] as [$name,$type,$in,$out]) {
            WorkSchedule::updateOrCreate(['name'=>$name], ['employee_type'=>$type,'working_days'=>$days,'check_in_time'=>$in,'late_tolerance_minutes'=>10,'check_out_time'=>$out,'earliest_check_in_time'=>'05:30','earliest_check_out_time'=>'09:30','is_active'=>true,'description'=>'Jadwal kerja awal yang dapat disesuaikan melalui master jadwal kerja.']);
        }
    }
}
