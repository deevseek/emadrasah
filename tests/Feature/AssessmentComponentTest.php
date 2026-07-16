<?php

declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{AcademicYear,AssessmentComponent,Classroom,Employee,GradeLevel,Semester,Subject,TeachingAssignment,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssessmentComponentTest extends TestCase
{
    use RefreshDatabase;
 public function test_component_weight_and_publish_workflow(): void
    {
        foreach(['assessments.view-own','assessments.create','assessments.publish'] as $p)Permission::findOrCreate($p);
        $u=User::factory()->create();
        $u->givePermissionTo(['assessments.view-own','assessments.create','assessments.publish']);
        $e=Employee::create(['user_id'=>$u->id,'name'=>'Guru','gender'=>'male','employment_type'=>'permanent','employee_status'=>'active','is_active'=>true]);
        $y=AcademicYear::create(['name'=>'2026','starts_at'=>'2026-01-01','ends_at'=>'2026-12-31','is_active'=>true]);
        $s=Semester::create(['academic_year_id'=>$y->id,'name'=>'Ganjil','term'=>'odd','starts_at'=>'2026-01-01','ends_at'=>'2026-06-30','is_active'=>true]);
        $gl=GradeLevel::create(['name'=>'1','code'=>'1','level'=>1,'is_active'=>true]);
        $c=Classroom::create(['academic_year_id'=>$y->id,'grade_level_id'=>$gl->id,'name'=>'1A','code'=>'1A','is_active'=>true]);
        $sub=Subject::create(['code'=>'MTK','name'=>'Matematika','category'=>'general','minimum_passing_grade'=>75,'is_active'=>true]);
        $ta=TeachingAssignment::create(['academic_year_id'=>$y->id,'semester_id'=>$s->id,'employee_id'=>$e->id,'classroom_id'=>$c->id,'subject_id'=>$sub->id,'is_active'=>true]);
        $this->actingAs($u)->post(route('assessment-components.store'),['teaching_assignment_id'=>$ta->id,'name'=>'UH 1','type'=>'daily_test','weight'=>40,'maximum_score'=>100,'is_required'=>1])->assertRedirect();
        $component=AssessmentComponent::firstOrFail();
        $this->patch(route('assessment-components.publish',$component))->assertRedirect();
        $this->assertEquals('published',$component->fresh()->status);
    }
}

