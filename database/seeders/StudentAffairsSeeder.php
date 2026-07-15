<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdmissionType; use App\Enums\EnrollmentStatus; use App\Enums\Gender; use App\Enums\GuardianRelationship; use App\Enums\StudentStatus; use App\Models\AcademicYear; use App\Models\Classroom; use App\Models\Guardian; use App\Models\Student; use App\Models\StudentEnrollment; use Illuminate\Database\Seeder;

class StudentAffairsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local','testing'])) return;
        $students=[['student_number'=>'SIS-FIK-001','national_student_number'=>'NISN-FIK-001','national_identity_number'=>'NIK-SIS-FIK-001','name'=>'Aisyah Data Fiktif','gender'=>Gender::Female->value],['student_number'=>'SIS-FIK-002','national_student_number'=>'NISN-FIK-002','national_identity_number'=>'NIK-SIS-FIK-002','name'=>'Bilal Data Fiktif','gender'=>Gender::Male->value]];
        foreach($students as $data){ Student::updateOrCreate(['student_number'=>$data['student_number']],$data+['family_card_number'=>'KK-FIKTIF-001','admission_type'=>AdmissionType::NewStudent->value,'student_status'=>StudentStatus::Active->value,'is_active'=>true]); }
        $guardians=[['national_identity_number'=>'NIK-WALI-FIK-001','name'=>'Wali Fiktif Satu','gender'=>Gender::Male->value,'phone'=>'080000000001'],['national_identity_number'=>'NIK-WALI-FIK-002','name'=>'Wali Fiktif Dua','gender'=>Gender::Female->value,'phone'=>'080000000002']];
        foreach($guardians as $data){ Guardian::updateOrCreate(['national_identity_number'=>$data['national_identity_number']],$data+['family_card_number'=>'KK-FIKTIF-001','monthly_income'=>0,'is_active'=>true]); }
        $wali=Guardian::where('national_identity_number','NIK-WALI-FIK-001')->first();
        foreach(Student::whereIn('student_number',['SIS-FIK-001','SIS-FIK-002'])->get() as $student){ $student->guardians()->syncWithoutDetaching([$wali->id=>['relationship'=>GuardianRelationship::Guardian->value,'is_primary'=>true,'is_emergency_contact'=>true,'lives_with_student'=>true,'financially_responsible'=>true]]); }
        $year=AcademicYear::where('is_active',true)->first(); $classroom=Classroom::where('academic_year_id',$year?->id)->first();
        if($year && $classroom){ foreach(Student::whereIn('student_number',['SIS-FIK-001','SIS-FIK-002'])->get() as $student){ StudentEnrollment::firstOrCreate(['student_id'=>$student->id,'academic_year_id'=>$year->id,'enrollment_status'=>EnrollmentStatus::Active->value],['classroom_id'=>$classroom->id,'enrolled_at'=>now()->toDateString()]); } }
    }
}
