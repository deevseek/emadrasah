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

final class CheckInAction
{
    public function __construct(private AttendanceScheduleService $schedules, private AttendanceService $attendance, private ActivityLogger $logger) {}

    public function execute(?Employee $employee, array $data, ?UploadedFile $photo = null): EmployeeAttendance
    {
        if (! $employee) throw ValidationException::withMessages(['attendance' => 'Akun pengguna belum terhubung dengan data pegawai.']);
        if (! $employee->is_active) throw ValidationException::withMessages(['attendance' => 'Pegawai nonaktif tidak dapat melakukan check-in.']);
        $now = CarbonImmutable::now($this->schedules->timezone());
        $schedule = $this->schedules->forEmployeeOn($employee, $now);
        if (! $schedule) throw ValidationException::withMessages(['attendance' => 'Hari ini tidak memiliki jadwal kerja aktif.']);
        if ($schedule->earliest_check_in_time && $now->lt($now->setTimeFromTimeString($schedule->earliest_check_in_time))) throw ValidationException::withMessages(['attendance' => 'Check-in terlalu awal dari batas jadwal.']);
        $path = $photo?->store('employee-attendances', 'private');
        try {
            return DB::transaction(function () use ($employee, $data, $now, $schedule, $path): EmployeeAttendance {
                if (EmployeeAttendance::where('employee_id', $employee->id)->whereDate('attendance_date', $now->toDateString())->lockForUpdate()->exists()) throw ValidationException::withMessages(['attendance' => 'Check-in hari ini sudah tercatat.']);
                [$status, $late] = $this->attendance->statusAndMinutes($now, $schedule);
                $record = EmployeeAttendance::create(['employee_id'=>$employee->id,'attendance_date'=>$now->toDateString(),'work_schedule_id'=>$schedule->id,'scheduled_check_in'=>$schedule->check_in_time,'scheduled_check_out'=>$schedule->check_out_time,'checked_in_at'=>$now,'status'=>$status,'late_minutes'=>$late,'check_in_latitude'=>$data['latitude'] ?? null,'check_in_longitude'=>$data['longitude'] ?? null,'check_in_accuracy'=>$data['accuracy'] ?? null,'check_in_photo_path'=>$path,'latitude'=>$data['latitude'] ?? null,'longitude'=>$data['longitude'] ?? null,'accuracy'=>$data['accuracy'] ?? null,'selfie_path'=>$path,'verification_status'=>'pending','source'=>'web','created_by'=>auth()->id()]);
                $this->logger->log('employee-attendance.checked-in', $record, [], $record->toArray());
                return $record;
            });
        } catch (\Throwable $e) { if ($path) Storage::disk('private')->delete($path); throw $e; }
    }
}
