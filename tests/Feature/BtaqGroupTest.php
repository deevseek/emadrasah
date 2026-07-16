<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{AcademicYear,BtaqGroup,BtaqGroupStudent,BtaqLevel,Classroom,Employee,GradeLevel,Semester,Student,StudentEnrollment,User};
use App\Services\BtaqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;


class BtaqGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_capacity_and_duplicate_active_membership_are_validated(): void
    {
        $user=User::factory()->create();
        $employee=Employee::create(['user_id'=>$user->id,'name'=>'Guru','gender'=>'male','employment_type'=>'permanent','employee_status'=>'active','is_active'=>true]);
        $year=AcademicYear::create(['name'=>'2026/2027','starts_at'=>'2026-07-01','ends_at'=>'2027-06-30','is_active'=>true]);
        $semester=Semester::create(['academic_year_id'=>$year->id,'name'=>'Ganjil','term'=>'odd','starts_at'=>'2026-07-01','ends_at'=>'2026-12-31','is_active'=>true]);
        $level=BtaqLevel::create(['code'=>'L1','name'=>'Pra Iqra','sequence'=>1,'is_active'=>true]);
        $group=BtaqGroup::create(['academic_year_id'=>$year->id,'semester_id'=>$semester->id,'name'=>'A','code'=>'A','employee_id'=>$employee->id,'btaq_level_id'=>$level->id,'capacity'=>1,'is_active'=>true]);
        $grade=GradeLevel::create(['name'=>'1','code'=>'1','level'=>1,'is_active'=>true]);
        $class=Classroom::create(['academic_year_id'=>$year->id,'grade_level_id'=>$grade->id,'name'=>'1A','code'=>'1A','is_active'=>true]);
        $student=Student::create(['name'=>'Siswa','gender'=>'male','admission_type'=>'new','student_status'=>'active','is_active'=>true]);
        StudentEnrollment::create(['student_id'=>$student->id,'academic_year_id'=>$year->id,'classroom_id'=>$class->id,'enrollment_status'=>'active']);
        app(BtaqService::class)->addMembers($group, [$student->id], $user->id);
        $this->assertDatabaseHas('btaq_group_students', ['student_id'=>$student->id,'status'=>'active']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(BtaqService::class)->addMembers($group, [$student->id], $user->id);
    }
}
