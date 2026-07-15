<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EmployeeStatus; use App\Enums\EmploymentType; use App\Enums\Gender; use App\Enums\SubjectCategory; use App\Models\AcademicYear; use App\Models\Classroom; use App\Models\Employee; use App\Models\GradeLevel; use App\Models\Subject; use Illuminate\Database\Seeder;

class AcademicMasterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 6) as $level) GradeLevel::updateOrCreate(['level'=>$level], ['name'=>'Kelas '.$level,'code'=>'K'.$level,'is_active'=>true]);
        foreach ([['PAI','Pendidikan Agama Islam',SubjectCategory::Religion],['BIN','Bahasa Indonesia',SubjectCategory::General],['MTK','Matematika',SubjectCategory::General],['BTAQ','BTAQ',SubjectCategory::Btaq],['MLK','Muatan Lokal',SubjectCategory::LocalContent]] as [$code,$name,$cat]) Subject::updateOrCreate(['code'=>$code], ['name'=>$name,'category'=>$cat->value,'minimum_passing_grade'=>75,'is_active'=>true]);
        if (app()->environment(['local','testing'])) {
            $employee = Employee::updateOrCreate(['employee_number'=>'PEG-001'], ['name'=>'Guru Contoh','gender'=>Gender::Female->value,'employment_type'=>EmploymentType::ClassTeacher->value,'employee_status'=>EmployeeStatus::Permanent->value,'email'=>'guru.contoh@example.test','is_active'=>true]);
            $year = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
            if ($year) foreach (range(1, 2) as $level) Classroom::updateOrCreate(['academic_year_id'=>$year->id,'code'=>$level.'A'], ['grade_level_id'=>GradeLevel::where('level',$level)->value('id'),'name'=>$level.'A','capacity'=>28,'homeroom_teacher_id'=>$employee->id,'room'=>'Ruang '.$level.'A','is_active'=>true]);
        }
    }
}
