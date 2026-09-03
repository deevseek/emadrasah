<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrd\StoreAttendanceDeviceRequest;
use App\Exceptions\AttendanceSecurityException;
use App\Models\{Personnel, PersonnelAttendanceDevice};
use App\Services\Hrd\AttendanceDeviceService;
use App\Services\Personnel\ResolvePersonnelAccount;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class AttendanceDeviceController extends Controller
{
    public function __construct(private ResolvePersonnelAccount $personnelAccounts) {}

    public function index(Request $request): View
    {
        $devices = PersonnelAttendanceDevice::query()
            ->with(['personnel:id,full_name,position', 'trustedBy:id,name'])
            ->when($request->filled('status'), function ($query) use ($request): void {
                match ($request->string('status')->toString()) {
                    'trusted' => $query->where('is_trusted', true)->whereNull('revoked_at'),
                    'pending' => $query->where('is_trusted', false)->whereNull('revoked_at'),
                    'revoked' => $query->whereNotNull('revoked_at'),
                    default => null,
                };
            })
            ->when($request->filled('search'), fn ($query) => $query->whereHas('personnel', fn ($personnel) => $personnel->where('full_name', 'like', '%'.$request->string('search')->toString().'%')))
            ->latest('last_seen_at')
            ->paginate(20)
            ->withQueryString();

        return view('hrd.attendance-devices.index', [
            'devices' => $devices,
        ]);
    }

    public function mine(Request $request): View
    {
        $personnel = $this->personnel($request);

        return view('hrd.attendance-devices.mine', [
            'personnel' => $personnel,
            'devices' => $personnel->attendanceDevices()->latest('created_at')->get(),
        ]);
    }

    public function store(StoreAttendanceDeviceRequest $request, AttendanceDeviceService $service): RedirectResponse
    {
        $service->register($this->personnel($request), $request->validated(), $request->user());

        return back()->with('status', 'Perangkat berhasil diajukan. Tunggu validasi Operator atau Super Admin sebelum digunakan untuk absensi.');
    }

    public function approve(PersonnelAttendanceDevice $device, AttendanceDeviceService $service): RedirectResponse
    {
        $service->approve($device, request()->user());

        return back()->with('status', 'Perangkat absensi berhasil disetujui.');
    }

    public function revoke(PersonnelAttendanceDevice $device, AttendanceDeviceService $service): RedirectResponse
    {
        $service->revoke($device, request()->user());

        return back()->with('status', 'Akses perangkat absensi berhasil dicabut.');
    }

    private function personnel(Request $request): Personnel
    {
        $personnel = $this->personnelAccounts->handle($request->user());
        if (! $personnel) {
            throw new AttendanceSecurityException('PERSONNEL_NOT_LINKED', 'Akun belum terhubung dengan personalia aktif. Hubungi Operator Madrasah.', 403);
        }

        return $personnel;
    }
}
