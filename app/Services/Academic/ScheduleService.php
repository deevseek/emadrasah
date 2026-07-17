<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{LessonSchedule, TeachingAssignment};
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(private ScheduleConflictService $conflicts, private ActivityLogger $logger) {}
    public function save(array $data, ?LessonSchedule $schedule = null): LessonSchedule
    {
        return DB::transaction(function () use ($data, $schedule) {
            $assignment = TeachingAssignment::with(['employee','classroom','subject'])->lockForUpdate()->findOrFail($data['teaching_assignment_id']);
            if (! $assignment->is_active || ! $assignment->employee?->is_active || ! $assignment->classroom?->is_active || ! $assignment->subject?->is_active) throw ValidationException::withMessages(['teaching_assignment_id'=>'Penugasan Mengajar harus aktif dan valid.']);
            $payload = $data + ['academic_year_id'=>$assignment->academic_year_id,'semester_id'=>$assignment->semester_id,'classroom_id'=>$assignment->classroom_id,'subject_id'=>$assignment->subject_id,'employee_id'=>$assignment->employee_id,'is_active'=>$data['is_active'] ?? true];
            LessonSchedule::where('semester_id',$payload['semester_id'])->where('day_of_week',$payload['day_of_week'])->where('starts_at','<',$payload['ends_at'])->where('ends_at','>',$payload['starts_at'])->lockForUpdate()->get();
            $this->conflicts->assertNoConflict($payload, $schedule);
            $old = $schedule?->getAttributes() ?? [];
            $schedule ? $schedule->update($payload) : $schedule = LessonSchedule::create($payload);
            $this->logger->log($old ? 'schedule.updated' : 'schedule.created', $schedule, $old, $schedule->getAttributes(), 'Jadwal Pelajaran disimpan.');
            return $schedule;
        });
    }
    public function toggle(LessonSchedule $schedule, bool $active): void { DB::transaction(function() use($schedule,$active){ $old=$schedule->getAttributes(); $schedule->update(['is_active'=>$active]); $this->logger->log('schedule.status-changed',$schedule,$old,$schedule->getAttributes(),'Status jadwal diubah.'); }); }
}
