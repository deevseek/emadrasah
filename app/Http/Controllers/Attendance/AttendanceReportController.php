<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttendanceReportController extends Controller
{
    public function index(Request $request): View { return view('attendance.reports.index', $this->data($request)); }
    public function export(Request $request): StreamedResponse { $records = $this->query($request)->get(); return Response::streamDownload(function () use ($records): void { $out = fopen('php://output', 'w'); fputcsv($out, ['Pegawai','Tanggal','Status','Check-in','Check-out','Terlambat','Pulang awal']); foreach ($records as $r) fputcsv($out, [$r->employee?->name, $r->attendance_date?->toDateString(), $r->status?->label(), $r->checked_in_at?->format('H:i'), $r->checked_out_at?->format('H:i'), $r->late_minutes, $r->early_leave_minutes]); fclose($out); }, 'laporan-kehadiran.csv', ['Content-Type' => 'text/csv']); }
    private function data(Request $request): array { $query = $this->query($request); return ['records' => (clone $query)->paginate(20)->withQueryString(), 'employees' => Employee::where('is_active', true)->orderBy('name')->get(), 'statuses' => AttendanceStatus::cases(), 'summary' => ['hari_kerja' => (clone $query)->count(), 'hadir' => (clone $query)->where('status', AttendanceStatus::Present)->count(), 'tepat_waktu' => (clone $query)->where('status', AttendanceStatus::Present)->where('late_minutes', 0)->count(), 'terlambat' => (clone $query)->where('status', AttendanceStatus::Late)->count(), 'total_menit_terlambat' => (clone $query)->sum('late_minutes'), 'pulang_awal' => (clone $query)->where('early_leave_minutes', '>', 0)->count(), 'izin' => (clone $query)->where('status', AttendanceStatus::Leave)->count(), 'sakit' => (clone $query)->where('status', AttendanceStatus::Sick)->count(), 'cuti' => (clone $query)->where('status', AttendanceStatus::Vacation)->count(), 'dinas' => (clone $query)->where('status', AttendanceStatus::Duty)->count(), 'alpha' => (clone $query)->where('status', AttendanceStatus::Alpha)->count()]]; }
    private function query(Request $request) { return EmployeeAttendance::with('employee')->filter($request->only(['date','from','to','month','status','employee_id','q']))->latest('attendance_date'); }
}
