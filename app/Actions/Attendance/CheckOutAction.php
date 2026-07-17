<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Services\ActivityLogger;
use App\Services\Attendance\AttendanceScheduleService;
use App\Services\Attendance\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class CheckOutAction
{
    public function __construct(private AttendanceScheduleService $schedules, private AttendanceService $attendance, private ActivityLogger $logger) {}
    public function execute(?Employee $employee, array $data = [], ?UploadedFile $photo = null): EmployeeAttendance
    {
        if (! $employee) throw ValidationException::withMessages(['attendance' => 'Akun pengguna belum terhubung dengan data pegawai.']);
        $now = CarbonImmutable::now($this->schedules->timezone());
        $path = $photo?->store('employee-attendances', 'private');
        try { return DB::transaction(function () use ($employee, $data, $now, $path): EmployeeAttendance {
            $record = EmployeeAttendance::where('employee_id', $employee->id)->whereDate('attendance_date', $now->toDateString())->lockForUpdate()->first();
            if (! $record) throw ValidationException::withMessages(['attendance' => 'Anda belum melakukan check-in.']);
            if ($record->checked_out_at) throw ValidationException::withMessages(['attendance' => 'Check-out hari ini sudah tercatat.']);
            $old = $record->toArray(); $early = $this->attendance->earlyLeaveMinutes($now, $record);
            $record->update(['checked_out_at'=>$now,'early_leave_minutes'=>$early,'check_out_latitude'=>$data['latitude'] ?? null,'check_out_longitude'=>$data['longitude'] ?? null,'check_out_accuracy'=>$data['accuracy'] ?? null,'check_out_photo_path'=>$path,'updated_by'=>auth()->id()]);
            $this->logger->log('employee-attendance.checked-out', $record, $old, $record->fresh()->toArray()); return $record;
        }); } catch (\Throwable $e) { if ($path) Storage::disk('private')->delete($path); throw $e; }
    }
}
