<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;

class TeacherWorkloadService
{
    public function summarize(?int $employeeId = null): Collection
    { return TeachingAssignment::with(['employee','classroom','subject','schedules'=>fn($q)=>$q->where('is_active',true)->where('entry_type','lesson')->where('counts_as_teaching_hour',true)])->where('is_active',true)->when($employeeId,fn($q)=>$q->where('employee_id',$employeeId))->get()->groupBy('employee_id')->map(function($rows){$weekly=$rows->sum('weekly_hours');$schedules=$rows->flatMap->schedules->unique(fn($schedule)=>$schedule->isSharedSession()?implode(':',[$schedule->employee_id,$schedule->semester_id,$schedule->day_of_week->value,$schedule->starts_at,$schedule->ends_at,$schedule->shared_session_code]):'schedule:'.$schedule->id);$scheduled=$schedules->sum('lesson_hours');return ['employee'=>$rows->first()->employee,'class_count'=>$rows->pluck('classroom_id')->unique()->count(),'subject_count'=>$rows->pluck('subject_id')->unique()->count(),'weekly_hours'=>$weekly,'scheduled_hours'=>$scheduled,'remaining_hours'=>$weekly-$scheduled,'over_hours'=>max(0,$scheduled-$weekly),'has_warning'=>$scheduled>$weekly,'assignments'=>$rows];}); }
}
