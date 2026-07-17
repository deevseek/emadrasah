<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Actions\Attendance\CheckInAction;
use App\Actions\Attendance\CheckOutAction;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\EmployeeAttendanceCorrectionRequest;
use App\Http\Requests\Attendance\EmployeeAttendanceVerifyRequest;
use App\Http\Requests\Attendance\EmployeeCheckInRequest;
use App\Http\Requests\Attendance\EmployeeCheckOutRequest;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Services\ActivityLogger;
use App\Services\Attendance\AttendanceScheduleService;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class EmployeeAttendanceController extends Controller
{
    public function mine(AttendanceScheduleService $schedules): View
    {
        $employee = auth()->user()->employee;
        abort_if($employee === null, 403, 'Akun pengguna belum terhubung dengan data pegawai.');
        $filters = request()->only(['month', 'status']);
        $records = $employee->attendances()->with('workSchedule')->filter($filters)->latest('attendance_date')->paginate(10)->withQueryString();
        $stats = ['hadir'=>(clone $employee->attendances())->where('status', AttendanceStatus::Present)->count(),'terlambat'=>(clone $employee->attendances())->where('status', AttendanceStatus::Late)->count(),'izin'=>(clone $employee->attendances())->where('status', AttendanceStatus::Leave)->count(),'sakit'=>(clone $employee->attendances())->where('status', AttendanceStatus::Sick)->count(),'alpha'=>(clone $employee->attendances())->where('status', AttendanceStatus::Alpha)->count()];
        return view('attendance.employee.mine', ['employee'=>$employee,'schedule'=>$schedules->forEmployeeOn($employee, now()->toImmutable()),'today'=>$employee->attendances()->whereDate('attendance_date', now()->toDateString())->first(),'records'=>$records,'stats'=>$stats,'statuses'=>AttendanceStatus::cases()]);
    }
    public function checkIn(EmployeeCheckInRequest $request, CheckInAction $action): RedirectResponse { $action->execute(auth()->user()->employee, $request->validated(), $request->file('photo') ?: $request->file('selfie')); return back()->with('status', 'Check-in berhasil.'); }
    public function checkOut(EmployeeCheckOutRequest $request, CheckOutAction $action): RedirectResponse { $record = $action->execute(auth()->user()->employee, $request->validated(), $request->file('photo')); $msg = $record->early_leave_minutes > 0 ? 'Anda pulang '.$record->early_leave_minutes.' menit lebih awal dari jadwal.' : 'Check-out berhasil.'; return back()->with('status', $msg); }
    public function index(): View { return view('attendance.employee.index', ['employees'=>Employee::where('is_active', true)->orderBy('name')->get(), 'records'=>EmployeeAttendance::with(['employee','workSchedule'])->filter(request()->only(['date','from','to','month','status','employee_id','verification_status','q']))->latest('attendance_date')->paginate(15)->withQueryString(), 'statuses'=>AttendanceStatus::cases(), 'verifications'=>AttendanceVerificationStatus::cases()]); }
    public function show(EmployeeAttendance $employeeAttendance): View { return view('attendance.employee.show', ['record'=>$employeeAttendance->load(['employee','verifier','workSchedule','corrections.user']), 'verifications'=>AttendanceVerificationStatus::cases(), 'statuses'=>AttendanceStatus::cases()]); }
    public function verify(EmployeeAttendanceVerifyRequest $request, EmployeeAttendance $employeeAttendance, ActivityLogger $logger): RedirectResponse { DB::transaction(function () use ($request, $employeeAttendance, $logger): void { $old=$employeeAttendance->toArray(); $employeeAttendance->update($request->validated()+['is_verified'=>$request->verification_status === 'verified','verified_by'=>auth()->id(),'verified_at'=>now()]); $logger->log('employee-attendance.verified', $employeeAttendance, $old, $employeeAttendance->fresh()->toArray()); }); return back()->with('status','Verifikasi kehadiran tersimpan.'); }
    public function correct(EmployeeAttendanceCorrectionRequest $request, EmployeeAttendance $employeeAttendance, AttendanceService $service, ActivityLogger $logger): RedirectResponse { DB::transaction(function () use ($request, $employeeAttendance, $service, $logger): void { $old=$employeeAttendance->only(['checked_in_at','checked_out_at','status','notes','late_minutes','early_leave_minutes']); $data=$request->validated(); $employeeAttendance->fill(['checked_in_at'=>$data['checked_in_at'] ?? null,'checked_out_at'=>$data['checked_out_at'] ?? null,'status'=>$data['status'],'notes'=>$data['notes'] ?? null,'correction_reason'=>$data['reason'],'updated_by'=>auth()->id()]); if ($employeeAttendance->checked_in_at && $employeeAttendance->workSchedule) [, $employeeAttendance->late_minutes] = $service->statusAndMinutes($employeeAttendance->checked_in_at->toImmutable(), $employeeAttendance->workSchedule); if ($employeeAttendance->checked_out_at) $employeeAttendance->early_leave_minutes = $service->earlyLeaveMinutes($employeeAttendance->checked_out_at->toImmutable(), $employeeAttendance); $employeeAttendance->save(); AttendanceCorrection::create(['employee_attendance_id'=>$employeeAttendance->id,'old_values'=>$old,'new_values'=>$employeeAttendance->only(array_keys($old)),'reason'=>$data['reason'],'corrected_by'=>auth()->id(),'corrected_at'=>now()]); $logger->log('employee-attendance.corrected', $employeeAttendance, $old, $employeeAttendance->fresh()->toArray(), $data['reason']); }); return back()->with('status','Koreksi kehadiran tersimpan dengan histori.'); }
}
