<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\LeaveStatus;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeLeaveRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class LeaveRequestService
{
    public function __construct(private ActivityLogger $logger, private AttendanceScheduleService $schedules) {}
    public function create(array $data, ?UploadedFile $file = null): EmployeeLeaveRequest
    {
        $path = $file?->store('employee-leaves', 'private');
        try { return DB::transaction(function () use ($data, $path): EmployeeLeaveRequest {
            $employee = \App\Models\Employee::findOrFail($data['employee_id']); if (! $employee->is_active) throw ValidationException::withMessages(['employee_id'=>'Pegawai nonaktif tidak dapat mengajukan perizinan.']);
            $overlap = EmployeeLeaveRequest::where('employee_id',$data['employee_id'])->whereNotIn('status',[LeaveStatus::Rejected->value,LeaveStatus::Cancelled->value])->whereDate('starts_at','<=',$data['ends_at'])->whereDate('ends_at','>=',$data['starts_at'])->lockForUpdate()->exists(); if ($overlap) throw ValidationException::withMessages(['starts_at'=>'Tanggal izin tumpang tindih dengan pengajuan lain.']);
            $days = $this->workingDays($employee, $data['starts_at'], $data['ends_at']);
            $leave = EmployeeLeaveRequest::create($data+['attachment_path'=>$path,'status'=>LeaveStatus::Pending,'submitted_at'=>now(),'total_days'=>$days]); $this->logger->log('employee-leave.created',$leave,[],$leave->toArray()); return $leave;
        }); } catch (\Throwable $e) { if ($path) Storage::disk('private')->delete($path); throw $e; }
    }
    public function approve(EmployeeLeaveRequest $leave): void
    {
        DB::transaction(function () use ($leave): void { $leave = EmployeeLeaveRequest::whereKey($leave->id)->lockForUpdate()->firstOrFail(); if ($leave->status !== LeaveStatus::Pending) throw ValidationException::withMessages(['status'=>'Pengajuan sudah diproses.']); $old=$leave->toArray(); $leave->update(['status'=>LeaveStatus::Approved,'approved_by'=>auth()->id(),'approved_at'=>now()]); for ($d=$leave->starts_at->copy(); $d->lte($leave->ends_at); $d->addDay()) { if (! $this->schedules->forEmployeeOn($leave->employee, $d->toImmutable())) continue; EmployeeAttendance::updateOrCreate(['employee_id'=>$leave->employee_id,'attendance_date'=>$d->toDateString()], ['status'=>$leave->type->attendanceStatus(),'notes'=>'Referensi pengajuan izin #'.$leave->id,'source'=>'leave','created_by'=>auth()->id(),'updated_by'=>auth()->id()]); } $this->logger->log('employee-leave.approved',$leave,$old,$leave->fresh()->toArray()); });
    }
    public function reject(EmployeeLeaveRequest $leave, string $reason): void
    {
        DB::transaction(function () use ($leave, $reason): void { $leave = EmployeeLeaveRequest::whereKey($leave->id)->lockForUpdate()->firstOrFail(); if ($leave->status !== LeaveStatus::Pending) throw ValidationException::withMessages(['status'=>'Pengajuan sudah diproses.']); $old=$leave->toArray(); $leave->update(['status'=>LeaveStatus::Rejected,'rejection_reason'=>$reason,'rejected_by'=>auth()->id(),'rejected_at'=>now()]); $this->logger->log('employee-leave.rejected',$leave,$old,$leave->fresh()->toArray(),$reason); });
    }
    private function workingDays($employee, string $start, string $end): int { $count=0; for($d=Carbon::parse($start); $d->lte(Carbon::parse($end)); $d->addDay()) if($this->schedules->forEmployeeOn($employee,$d->toImmutable())) $count++; return max(1,$count); }
}
