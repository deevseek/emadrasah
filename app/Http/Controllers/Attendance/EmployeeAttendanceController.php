<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\EmployeeAttendanceVerifyRequest;
use App\Http\Requests\Attendance\EmployeeCheckInRequest;
use App\Http\Requests\Attendance\EmployeeCheckOutRequest;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Services\Attendance\EmployeeAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class EmployeeAttendanceController extends Controller
{
    public function mine(): View
    {
        $employee = auth()->user()->employee;
        abort_if($employee === null, 403, 'Akun pengguna belum terhubung dengan data pegawai.');
        abort_if(! $employee->is_active, 403, 'Pegawai nonaktif tidak dapat menggunakan absensi.');

        return view('attendance.employee.mine', [
            'today' => $employee->attendances()->whereDate('attendance_date', now()->toDateString())->first(),
            'records' => $employee->attendances()->latest('attendance_date')->paginate(10),
        ]);
    }

    public function checkIn(EmployeeCheckInRequest $request, EmployeeAttendanceService $service): RedirectResponse
    {
        $service->checkIn(auth()->user()->employee, $request->validated(), $request->file('selfie'));

        return back()->with('status', 'Check-in berhasil.');
    }

    public function checkOut(EmployeeCheckOutRequest $request, EmployeeAttendanceService $service): RedirectResponse
    {
        $service->checkOut(auth()->user()->employee);

        return back()->with('status', 'Check-out berhasil.');
    }

    public function index(): View
    {
        return view('attendance.employee.index', [
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(),
            'records' => EmployeeAttendance::query()
                ->with('employee')
                ->filter(request()->only(['date', 'month', 'status', 'employee_id', 'work_schedule_type']))
                ->latest('attendance_date')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(EmployeeAttendance $employeeAttendance): View
    {
        return view('attendance.employee.show', [
            'record' => $employeeAttendance->load('employee', 'verifier'),
        ]);
    }

    public function verify(
        EmployeeAttendanceVerifyRequest $request,
        EmployeeAttendance $employeeAttendance,
        EmployeeAttendanceService $service,
    ): RedirectResponse {
        $service->verify($employeeAttendance, $request->validated());

        return back()->with('status', 'Absensi terverifikasi dan koreksi tersimpan.');
    }
}
