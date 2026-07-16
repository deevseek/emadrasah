<?php

declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{AcademicYear,AssessmentComponent,Classroom,Employee,GradeLevel,PredicateRange,Semester,Student,StudentEnrollment,Subject,TeachingAssignment,User};
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentScoreTest extends TestCase
{
    use RefreshDatabase;
 public function test_bulk_score_remedial_final_predicate_and_class_validation(): void
    {$u=User::factory()->create();
        $this->actingAs($u);
        PredicateRange::create(['code'=>'A','label'=>'A','minimum_score'=>90,'maximum_score'=>100,'sequence'=>1,'is_active'=>true]);
        PredicateRange::create(['code'=>'B','label'=>'B','minimum_score'=>0,'maximum_score'=>89.99,'sequence'=>2,'is_active'=>true]);
        $e=Employee::create(['name'=>'Guru','gender'=>'male','employment_type'=>'permanent','employee_status'=>'active','is_active'=>true]);
        $y=AcademicYear::create(['name'=>'2026','starts_at'=>'2026-01-01','ends_at'=>'2026-12-31','is_active'=>true]);
        $s=Semester::create(['academic_year_id'=>$y->id,'name'=>'Ganjil','term'=>'odd','starts_at'=>'2026-01-01','ends_at'=>'2026-06-30','is_active'=>true]);
        $gl=GradeLevel::create(['name'=>'1','code'=>'1','level'=>1,'is_active'=>true]);
        $c=Classroom::create(['academic_year_id'=>$y->id,'grade_level_id'=>$gl->id,'name'=>'1A','code'=>'1A','is_active'=>true]);
        $sub=Subject::create(['code'=>'IPA','name'=>'IPA','category'=>'general','minimum_passing_grade'=>75,'is_active'=>true]);
        $ta=TeachingAssignment::create(['academic_year_id'=>$y->id,'semester_id'=>$s->id,'employee_id'=>$e->id,'classroom_id'=>$c->id,'subject_id'=>$sub->id,'is_active'=>true]);
        $ac=AssessmentComponent::create(['academic_year_id'=>$y->id,'semester_id'=>$s->id,'classroom_id'=>$c->id,'subject_id'=>$sub->id,'teaching_assignment_id'=>$ta->id,'employee_id'=>$e->id,'name'=>'Tugas','type'=>'assignment','weight'=>100,'maximum_score'=>100,'is_required'=>true,'created_by'=>$u->id]);
        $st=Student::create(['name'=>'S','gender'=>'male','admission_type'=>'new','student_status'=>'active','is_active'=>true]);
        StudentEnrollment::create(['student_id'=>$st->id,'academic_year_id'=>$y->id,'classroom_id'=>$c->id,'enrollment_status'=>'active']);
        app(AssessmentService::class)->storeScores($ac,[$st->id=>['score'=>80,'remedial_score'=>95]],$u->id);
        $this->assertDatabaseHas('student_scores',['student_id'=>$st->id,'final_score'=>95,'predicate'=>'A']);
    }
}

