<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Services\Foundation\AcademicPeriodService;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\View\View;
use App\Models\{AcademicYear, Classroom, ClassroomJournal, ClassroomMembership, Personnel, Student, StudentAttendance, TeachingJournal};

class DashboardController extends Controller
{
    public function __invoke(SchoolProfileService $service, AcademicPeriodService $periodService): View
    {
        return view('dashboard', ['title' => 'Dashboard', 'profile' => $service->current(), 'academicPeriod' => $periodService->current(), 'personnelStats' => ['total'=>Personnel::count(),'active'=>Personnel::where('is_active',true)->count(),'without_account'=>Personnel::whereNull('user_id')->count()],'studentStats'=>['total'=>Student::count(),'active'=>Student::where('status','active')->count(),'incomplete_parents'=>Student::where(fn($q)=>$q->whereNull('father_name')->orWhereNull('mother_name'))->count()],'classroomStats'=>$this->classroomStats(request()->user()),'academicToday'=>$this->academicToday()]);
    }
    private function academicToday(): array { $year=AcademicYear::where('is_active',true)->value('id'); $rooms=Classroom::where('academic_year_id',$year)->where('is_active',true)->count(); $today=StudentAttendance::whereDate('attendance_date',today())->where('academic_year_id',$year); $recorded=(clone $today)->distinct('classroom_id')->count('classroom_id'); $classroomJournals=ClassroomJournal::where('academic_year_id',$year)->whereDate('journal_date',today())->count(); return ['recorded'=>$recorded,'pending'=>max(0,$rooms-$recorded),'teaching_journals'=>TeachingJournal::where('academic_year_id',$year)->whereDate('journal_date',today())->count(),'classroom_journals'=>$classroomJournals,'classroom_journals_pending'=>max(0,$rooms-$classroomJournals),'present'=>(clone $today)->where('status','present')->count(),'sick'=>(clone $today)->where('status','sick')->count(),'permitted'=>(clone $today)->where('status','permitted')->count(),'absent'=>(clone $today)->where('status','absent')->count()]; }
    private function classroomStats($user): array { $year=AcademicYear::where('is_active',true)->value('id'); if (! $year) return ['total'=>0,'unplaced'=>Student::where('status','active')->count(),'without_homeroom'=>0,'own'=>collect()]; $rooms=Classroom::where('academic_year_id',$year); $placed=ClassroomMembership::where('academic_year_id',$year)->where('status','active')->distinct('student_id')->count('student_id'); return ['total'=>(clone $rooms)->count(),'unplaced'=>max(0,Student::where('status','active')->count()-$placed),'without_homeroom'=>(clone $rooms)->whereNull('homeroom_personnel_id')->count(),'own'=>(clone $rooms)->where('homeroom_personnel_id',$user?->personnel?->id??0)->with('gradeLevel')->withCount('activeMemberships')->get()]; }
}
