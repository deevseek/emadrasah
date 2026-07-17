<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Enums\StudentAttendanceSessionStatus;
use App\Models\StudentAttendanceSession;
use App\Models\User;
use App\Services\Attendance\StudentAttendanceService;
use Illuminate\Support\Facades\DB;

class FinalizeClassAttendanceAction
{
    public function __construct(private StudentAttendanceService $service) {}
    public function execute(StudentAttendanceSession $session, User $user): void
    {
        DB::transaction(function () use ($session, $user): void {
            $session = StudentAttendanceSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($session->status === StudentAttendanceSessionStatus::Final, 422, 'Absensi sudah final.');
            $eligible = $this->service->eligibleEnrollments($session->classroom, $session->attendance_date);
            $count = $session->attendances()->whereIn('student_enrollment_id', $eligible->pluck('id'))->whereNotNull('status')->lockForUpdate()->count();
            abort_if($eligible->count() === 0 || $count !== $eligible->count(), 422, 'Seluruh siswa aktif wajib memiliki status sebelum finalisasi.');
            $session->attendances()->update(['finalized_by'=>$user->id,'finalized_at'=>now()]);
            $session->update(['status'=>StudentAttendanceSessionStatus::Final,'finalized_by'=>$user->id,'finalized_at'=>now()]);
            activity('student-attendances')->causedBy($user)->performedOn($session)->withProperties(['kelas'=>$session->classroom->name,'tanggal'=>$session->attendance_date->toDateString()])->log('Absensi siswa difinalisasi');
        });
    }
}
