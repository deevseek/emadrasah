<?php

declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{AcademicYear,AssessmentComponent,Classroom,Employee,Extracurricular,GradeLevel,PredicateRange,Semester,Student,StudentAchievement,StudentAttitudeNote,StudentEnrollment,StudentExtracurricular,Subject,TeachingAssignment,User};
use App\Services\{AssessmentService,ReportCardService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportCardTest extends TestCase
{
    use RefreshDatabase;
 public function test_generate_idempotent_submit_approve_lock_reopen_and_print(): void
    {
        foreach(['report-cards.view','report-cards.submit','report-cards.approve','report-cards.lock','report-cards.reopen','report-cards.print'] as $p)Permission::findOrCreate($p);
        $u=User::factory()->create();
        $u->givePermissionTo(['report-cards.view','report-cards.submit','report-cards.approve','report-cards.lock','report-cards.reopen','report-cards.print']);
        $this->actingAs($u);
        PredicateRange::create(['code'=>'A','label'=>'A','minimum_score'=>0,'maximum_score'=>100,'sequence'=>1,'is_active'=>true]);
        $e=Employee::create(['user_id'=>$u->id,'name'=>'Wali','gender'=>'male','employment_type'=>'permanent','employee_status'=>'active','is_active'=>true]);
        $y=AcademicYear::create(['name'=>'2026','starts_at'=>'2026-01-01','ends_at'=>'2026-12-31','is_active'=>true]);
        $s=Semester::create(['academic_year_id'=>$y->id,'name'=>'Ganjil','term'=>'odd','starts_at'=>'2026-01-01','ends_at'=>'2026-06-30','is_active'=>true]);
        $gl=GradeLevel::create(['name'=>'1','code'=>'1','level'=>1,'is_active'=>true]);
        $c=Classroom::create(['academic_year_id'=>$y->id,'grade_level_id'=>$gl->id,'name'=>'1A','code'=>'1A','homeroom_teacher_id'=>$e->id,'is_active'=>true]);
        $sub=Subject::create(['code'=>'BIN','name'=>'Bahasa Indonesia','category'=>'general','minimum_passing_grade'=>75,'is_active'=>true]);
        $ta=TeachingAssignment::create(['academic_year_id'=>$y->id,'semester_id'=>$s->id,'employee_id'=>$e->id,'classroom_id'=>$c->id,'subject_id'=>$sub->id,'is_active'=>true]);
        $st=Student::create(['name'=>'S','gender'=>'male','admission_type'=>'new','student_status'=>'active','is_active'=>true]);
        $en=StudentEnrollment::create(['student_id'=>$st->id,'academic_year_id'=>$y->id,'classroom_id'=>$c->id,'enrollment_status'=>'active']);
        $ac=AssessmentComponent::create(['academic_year_id'=>$y->id,'semester_id'=>$s->id,'classroom_id'=>$c->id,'subject_id'=>$sub->id,'teaching_assignment_id'=>$ta->id,'employee_id'=>$e->id,'name'=>'NA','type'=>'final_exam','weight'=>100,'maximum_score'=>100,'is_required'=>true,'created_by'=>$u->id]);
        app(AssessmentService::class)->storeScores($ac,[$st->id=>['score'=>88]],$u->id);
        StudentAttitudeNote::create(['student_id'=>$st->id,'classroom_id'=>$c->id,'academic_year_id'=>$y->id,'semester_id'=>$s->id,'entered_by'=>$u->id,'general_notes'=>'Baik']);
        $ex=Extracurricular::create(['code'=>'PRM','name'=>'Pramuka','is_active'=>true]);
        StudentExtracurricular::create(['student_id'=>$st->id,'extracurricular_id'=>$ex->id,'academic_year_id'=>$y->id,'semester_id'=>$s->id,'predicate'=>'A','is_active'=>true]);
        StudentAchievement::create(['student_id'=>$st->id,'academic_year_id'=>$y->id,'semester_id'=>$s->id,'achievement_type'=>'academic','name'=>'Juara','level'=>'Kelas','created_by'=>$u->id]);
        $card=app(ReportCardService::class)->generate($en,$s->id);
        $again=app(ReportCardService::class)->generate($en,$s->id);
        $this->assertEquals($card->id,$again->id);
        $this->patch(route('report-cards.submit',$card))->assertRedirect();
        $this->patch(route('report-cards.approve',$card))->assertRedirect();
        $this->patch(route('report-cards.lock',$card))->assertRedirect();
        $this->patch(route('report-cards.reopen',$card),['reason'=>'Perbaikan'])->assertRedirect();
        $this->get(route('report-cards.print',$card))->assertOk()->assertSee('Rapor Siswa');
        $this->assertDatabaseHas('report_card_status_histories',['report_card_id'=>$card->id,'to_status'=>'reopened']);
    }
}

