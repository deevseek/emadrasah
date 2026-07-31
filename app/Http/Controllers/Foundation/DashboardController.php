<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Services\Foundation\AcademicPeriodService;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\View\View;
use App\Models\{AcademicYear, Classroom, ClassroomMembership, Personnel, Student, TeachingAssignmentSet};
use App\Services\TeachingAssignments\{TeachingAssignmentConflictService, TeachingAssignmentReportService};

class DashboardController extends Controller
{
    public function __invoke(SchoolProfileService $service, AcademicPeriodService $periodService): View
    {
        return view('dashboard', ['title' => 'Dashboard', 'profile' => $service->current(), 'academicPeriod' => $periodService->current(), 'personnelStats' => ['total'=>Personnel::count(),'active'=>Personnel::where('is_active',true)->count(),'without_account'=>Personnel::whereNull('user_id')->count()],'studentStats'=>['total'=>Student::count(),'active'=>Student::where('status','active')->count(),'incomplete_parents'=>Student::where(fn($q)=>$q->whereNull('father_name')->orWhereNull('mother_name'))->count()],'classroomStats'=>$this->classroomStats(request()->user()), 'teachingAssignmentStats'=>$this->teachingAssignmentStats(request()->user())]);
    }
    private function teachingAssignmentStats($user): array
    {
        $set = TeachingAssignmentSet::with('academicYear')->where('status', 'active')->latest('activated_at')->first();
        if (! $set) return ['set' => null, 'teachers' => 0, 'complete' => 0, 'classrooms' => 0, 'conflicts' => 0, 'own' => null];
        $checks = app(\App\Services\TeachingAssignments\TeachingAssignmentReadinessService::class)->classrooms($set);
        $own = $user?->personnel ? app(TeachingAssignmentReportService::class)->personnel($set, $user->personnel) : null;
        return ['set' => $set, 'teachers' => $set->assignments()->distinct('personnel_id')->count('personnel_id'), 'complete' => $checks->where('status', 'Lengkap')->count(), 'classrooms' => $checks->count(), 'conflicts' => (int) $checks->sum('conflicts'), 'own' => $own];
    }

    private function classroomStats($user): array { $year=AcademicYear::where('is_active',true)->value('id'); if (! $year) return ['total'=>0,'unplaced'=>Student::where('status','active')->count(),'without_homeroom'=>0,'own'=>collect()]; $rooms=Classroom::where('academic_year_id',$year); $placed=ClassroomMembership::where('academic_year_id',$year)->where('status','active')->distinct('student_id')->count('student_id'); return ['total'=>(clone $rooms)->count(),'unplaced'=>max(0,Student::where('status','active')->count()-$placed),'without_homeroom'=>(clone $rooms)->whereNull('homeroom_personnel_id')->count(),'own'=>(clone $rooms)->where('homeroom_personnel_id',$user?->personnel?->id??0)->with('gradeLevel')->withCount('activeMemberships')->get()]; }
}
