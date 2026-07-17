<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Models\StudentAttendance;
use App\Models\StudentAttendanceCorrection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CorrectStudentAttendanceAction
{
    public function execute(StudentAttendance $attendance, array $data, User $user): void
    {
        DB::transaction(function () use ($attendance, $data, $user): void {
            $attendance = StudentAttendance::query()->whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            $old = $attendance->only(['status','arrival_time','departure_time','late_minutes','early_leave_minutes','reason','notes']);
            $new = array_merge($old, $data);
            StudentAttendanceCorrection::query()->create(['student_attendance_id'=>$attendance->id,'corrected_by'=>$user->id,'old_status'=>(string) $attendance->status->value,'new_status'=>$new['status'],'old_values'=>$old,'new_values'=>$new,'reason'=>$data['correction_reason']]);
            $attendance->fill($new + ['corrected_by'=>$user->id,'corrected_at'=>now(),'correction_reason'=>$data['correction_reason']])->save();
            activity('student-attendances')->causedBy($user)->performedOn($attendance)->withProperties(['siswa'=>$attendance->student?->name,'status_lama'=>$old['status'] instanceof \BackedEnum ? $old['status']->value : $old['status'],'status_baru'=>$new['status'],'alasan'=>$data['correction_reason']])->log('Status siswa dikoreksi');
        });
    }
}
