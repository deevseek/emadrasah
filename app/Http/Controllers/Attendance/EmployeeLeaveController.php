<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\EmployeeLeaveApprovalRequest;
use App\Http\Requests\Attendance\EmployeeLeaveStoreRequest;
use App\Models\EmployeeLeaveRequest;
use App\Services\Attendance\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Response;

final class EmployeeLeaveController extends Controller
{
    public function index(): View
    {
        $query = EmployeeLeaveRequest::query()
            ->with('employee')
            ->when(! auth()->user()->can('employee-leaves.view'), function ($query): void {
                $query->where('employee_id', auth()->user()->employee?->id);
            });

        return view('attendance.leaves.index', [
            'leaves' => $query->latest()->paginate(15),
        ]);
    }

    public function approvals(): View
    {
        return view('attendance.leaves.approvals', ['leaves' => EmployeeLeaveRequest::query()->with('employee')->where('status', LeaveStatus::Pending)->latest()->paginate(15)]);
    }

    public function export(): StreamedResponse
    {
        $leaves = EmployeeLeaveRequest::query()->with('employee')->latest()->get();
        return Response::streamDownload(function () use ($leaves): void { $out = fopen('php://output', 'w'); fputcsv($out, ['Pegawai','Mulai','Selesai','Jenis','Status','Alasan']); foreach ($leaves as $leave) fputcsv($out, [$leave->employee?->name, $leave->starts_at?->toDateString(), $leave->ends_at?->toDateString(), $leave->type?->value, $leave->status?->label(), $leave->reason]); fclose($out); }, 'pengajuan-izin.csv', ['Content-Type' => 'text/csv']);
    }

    public function create(): View
    {
        abort_if(auth()->user()->employee === null, 403, 'Akun pengguna belum terhubung dengan data pegawai.');

        return view('attendance.leaves.form');
    }

    public function store(EmployeeLeaveStoreRequest $request, LeaveRequestService $service): RedirectResponse
    {
        abort_if(auth()->user()->employee === null, 403, 'Akun pengguna belum terhubung dengan data pegawai.');

        $service->create(
            $request->validated() + ['employee_id' => auth()->user()->employee->id],
            $request->file('attachment')
        );

        return redirect()
            ->route('employee-leaves.index')
            ->with('status', 'Pengajuan izin dibuat.');
    }

    public function show(EmployeeLeaveRequest $employeeLeave): View
    {
        $this->authorizeAccess($employeeLeave);

        return view('attendance.leaves.show', [
            'leave' => $employeeLeave->load('employee', 'approver'),
        ]);
    }

    public function cancel(EmployeeLeaveRequest $employeeLeave): RedirectResponse
    {
        $this->authorizeOwner($employeeLeave);
        abort_unless($employeeLeave->status === LeaveStatus::Pending, 422, 'Hanya pengajuan pending yang dapat dibatalkan.');

        $employeeLeave->update(['status' => LeaveStatus::Cancelled]);

        activity('attendance')
            ->performedOn($employeeLeave)
            ->causedBy(auth()->user())
            ->event('employee-leave.cancelled')
            ->log('Izin dibatalkan');

        return back()->with('status', 'Pengajuan dibatalkan.');
    }

    public function approve(
        EmployeeLeaveApprovalRequest $request,
        EmployeeLeaveRequest $employeeLeave,
        LeaveRequestService $service,
    ): RedirectResponse {
        abort_unless($request->user()?->can('employee-leaves.approve'), 403);

        $service->approve($employeeLeave);

        return back()->with('status', 'Pengajuan disetujui.');
    }

    public function reject(
        EmployeeLeaveApprovalRequest $request,
        EmployeeLeaveRequest $employeeLeave,
        LeaveRequestService $service,
    ): RedirectResponse {
        abort_unless($request->user()?->can('employee-leaves.reject'), 403);
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $service->reject($employeeLeave, $validated['rejection_reason']);

        return back()->with('status', 'Pengajuan ditolak.');
    }

    public function download(EmployeeLeaveRequest $employeeLeave): StreamedResponse
    {
        $this->authorizeAccess($employeeLeave);
        abort_unless($employeeLeave->attachment_path, 404);

        return Storage::disk('private')->download($employeeLeave->attachment_path);
    }

    private function authorizeAccess(EmployeeLeaveRequest $employeeLeave): void
    {
        if (auth()->user()->can('employee-leaves.view')) {
            return;
        }

        $this->authorizeOwner($employeeLeave);
    }

    private function authorizeOwner(EmployeeLeaveRequest $employeeLeave): void
    {
        abort_unless(
            $employeeLeave->employee_id === auth()->user()->employee?->id,
            403,
            'Anda tidak berwenang membuka pengajuan izin pegawai lain.'
        );
    }
}
