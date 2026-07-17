<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;

class TeacherWorkloadService
{
    public function summarize(?int $employeeId = null): Collection
    { return TeachingAssignment::with(['employee','classroom','subject','schedules'])->where('is_active',true)->when($employeeId,fn($q)=>$q->where('employee_id',$employeeId))->get()->groupBy('employee_id')->map(fn($rows)=>['employee'=>$rows->first()->employee,'class_count'=>$rows->pluck('classroom_id')->unique()->count(),'subject_count'=>$rows->pluck('subject_id')->unique()->count(),'weekly_hours'=>$rows->sum('weekly_hours'),'scheduled_hours'=>$rows->flatMap->schedules->where('is_active',true)->sum('lesson_hours'),'remaining_hours'=>max(0,$rows->sum('weekly_hours')-$rows->flatMap->schedules->where('is_active',true)->sum('lesson_hours')),'assignments'=>$rows]); }
}
