<?php

declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{AcademicYear,BtaqGroup,BtaqJournal,BtaqLevel,Employee,Semester,Student,User};
use App\Services\BtaqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BtaqJournalTest extends TestCase
{
    use RefreshDatabase;
 public function test_mass_journal_submit_verify_and_reject(): void
    {
        foreach(['btaq-journals.view-own','btaq-journals.submit','btaq-journals.verify','btaq-journals.reject'] as $p) \Spatie\Permission\Models\Permission::findOrCreate($p);
        $user=User::factory()->create();
        $user->givePermissionTo(['btaq-journals.view-own','btaq-journals.submit','btaq-journals.verify','btaq-journals.reject']);
        $this->actingAs($user);
        $e=Employee::create(['name'=>'Guru','gender'=>'male','employment_type'=>'permanent','employee_status'=>'active','is_active'=>true]);
        $y=AcademicYear::create(['name'=>'2026','starts_at'=>'2026-01-01','ends_at'=>'2026-12-31','is_active'=>true]);
        $s=Semester::create(['academic_year_id'=>$y->id,'name'=>'Ganjil','term'=>'odd','starts_at'=>'2026-01-01','ends_at'=>'2026-06-30','is_active'=>true]);
        $l=BtaqLevel::create(['code'=>'L','name'=>'L','sequence'=>1,'is_active'=>true]);
        $g=BtaqGroup::create(['academic_year_id'=>$y->id,'semester_id'=>$s->id,'name'=>'G','code'=>'G','employee_id'=>$e->id,'btaq_level_id'=>$l->id,'is_active'=>true]);
        $st=Student::create(['name'=>'S','gender'=>'male','admission_type'=>'new','student_status'=>'active','is_active'=>true]);
        $j=app(BtaqService::class)->saveJournal(['btaq_group_id'=>$g->id,'journal_date'=>'2026-07-16','status'=>'draft'],[$st->id=>['attendance_status'=>'present','progress_status'=>'needs_guidance','reading_score'=>80]],$user->id);
        $this->assertDatabaseHas('btaq_journal_students',['btaq_journal_id'=>$j->id]);
        $this->patch(route('btaq-journals.submit',$j))->assertRedirect();
        $this->patch(route('btaq-journals.verify',$j))->assertRedirect();
        $this->patch(route('btaq-journals.reject',$j),['rejection_reason'=>'Perbaiki'])->assertRedirect();
    }
}

