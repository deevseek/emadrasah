<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Services\Attendance\AttendanceScheduleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MarkEmployeeAbsent extends Command
{
    protected $signature = 'attendance:mark-absent {date?}';
    protected $description = 'Menandai alpha pegawai aktif secara idempotent setelah hari kerja selesai.';

    public function handle(AttendanceScheduleService $schedules): int
    {
        $date = now($schedules->timezone())->parse($this->argument('date') ?: now($schedules->timezone())->subDay()->toDateString())->toImmutable();
        $count = 0;
        Employee::where('is_active', true)->chunkById(100, function ($employees) use ($date, $schedules, &$count): void {
            foreach ($employees as $employee) {
                DB::transaction(function () use ($employee, $date, $schedules, &$count): void {
                    $schedule = $schedules->forEmployeeOn($employee, $date);
                    if (! $schedule) return;
                    $exists = EmployeeAttendance::where('employee_id', $employee->id)->whereDate('attendance_date', $date->toDateString())->lockForUpdate()->exists();
                    if ($exists) return;
                    EmployeeAttendance::create(['employee_id'=>$employee->id,'attendance_date'=>$date->toDateString(),'work_schedule_id'=>$schedule->id,'scheduled_check_in'=>$schedule->check_in_time,'scheduled_check_out'=>$schedule->check_out_time,'status'=>AttendanceStatus::Alpha,'source'=>'auto-alpha']);
                    $count++;
                });
            }
        });
        $this->info("{$count} data alpha ditandai.");
        return self::SUCCESS;
    }
}
