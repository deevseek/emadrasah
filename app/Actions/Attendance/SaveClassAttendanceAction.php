<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Enums\StudentAttendanceSessionStatus;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceSession;
use App\Models\User;
use App\Services\Attendance\StudentAttendanceService;
use Illuminate\Support\Facades\DB;

class SaveClassAttendanceAction
{
    public function __construct(private StudentAttendanceService $service) {}
    public function execute(StudentAttendanceSession $session, array $rows, User $user): void
    {
        DB::transaction(function () use ($session, $rows, $user): void {
            $session = StudentAttendanceSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($session->status === StudentAttendanceSessionStatus::Final, 422, 'Absensi final hanya dapat diubah melalui koreksi.');
            $enrollments = $this->service->eligibleEnrollments($session->classroom, $session->attendance_date)->keyBy('id');
            foreach ($rows as $enrollmentId => $row) {
                $enrollment = $enrollments->get((int) $enrollmentId); if (! $enrollment) { abort(422, 'Siswa tidak termasuk penempatan aktif kelas ini.'); }
                $attendance = StudentAttendance::query()->where('student_id', $enrollment->student_id)->whereDate('attendance_date', $session->attendance_date)->lockForUpdate()->first();
                $path = $this->service->replaceAttachment($row['attachment'] ?? null, $attendance?->attachment_path);
                StudentAttendance::query()->updateOrCreate(['student_id'=>$enrollment->student_id,'attendance_date'=>$session->attendance_date->toDateString()], ['student_attendance_session_id'=>$session->id,'student_enrollment_id'=>$enrollment->id,'classroom_id'=>$session->classroom_id,'academic_year_id'=>$session->academic_year_id,'semester_id'=>$session->semester_id,'status'=>$row['status'],'arrival_time'=>$row['arrival_time'] ?? null,'departure_time'=>$row['departure_time'] ?? null,'late_minutes'=>$row['late_minutes'] ?? 0,'early_leave_minutes'=>$row['early_leave_minutes'] ?? 0,'reason'=>$row['reason'] ?? null,'notes'=>$row['notes'] ?? null,'attachment_path'=>$path,'recorded_by'=>$user->id]);
            }
            $session->update(['recorded_by'=>$user->id, 'status'=>StudentAttendanceSessionStatus::Draft]);
            activity('student-attendances')->causedBy($user)->performedOn($session)->withProperties(['kelas'=>$session->classroom->name,'tanggal'=>$session->attendance_date->toDateString()])->log('Draft absensi siswa disimpan');
        });
    }
}
