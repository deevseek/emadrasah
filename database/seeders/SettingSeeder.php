<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder { public function run(): void { foreach ([['attendance','school_latitude','0','decimal'],['attendance','school_longitude','0','decimal'],['attendance','radius_meters','100','integer'],['attendance','late_tolerance_minutes','10','integer'],['attendance','regular_checkout_time','13:30','time'],['attendance','btaq_checkout_time','10:30','time'],['attendance','full_day_checkout_time','15:00','time'],['features','face_recognition_enabled','false','boolean'],['features','student_savings_enabled','false','boolean'],['features','whatsapp_enabled','false','boolean'],['whatsapp','enabled','false','boolean'],['backup','retention_days','14','integer'],['documents','receipt_number_format','KWT/{YEAR}/{MONTH}/{SEQ}','string'],['documents','letter_number_format','{SEQ}/MI-MNU/{MONTH_ROMAN}/{YEAR}','string'],['upload','max_file_size_kb','2048','integer'],['notification','email_enabled','false','boolean']] as $s) SchoolSetting::firstOrCreate(['group'=>$s[0], 'key'=>$s[1]], ['value'=>$s[2], 'type'=>$s[3], 'is_public'=>false]); } }
