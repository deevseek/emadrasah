<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrd\StoreAttendanceDeviceRequest;
use App\Models\{Personnel, PersonnelAttendanceDevice};
use App\Services\Hrd\AttendanceDeviceService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class AttendanceDeviceController extends Controller
{
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
            'personnel' => Personnel::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name', 'position']),
        ]);
    }

    public function store(StoreAttendanceDeviceRequest $request, AttendanceDeviceService $service): RedirectResponse
    {
        $service->register($request->validated(), $request->user());

        return back()->with('status', 'Perangkat absensi berhasil didaftarkan dan langsung dapat digunakan.');
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
}
