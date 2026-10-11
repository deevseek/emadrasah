<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\RfidDeviceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rfid\StoreRfidDeviceRequest;
use App\Models\RfidDevice;
use App\Services\Rfid\RfidDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RfidDeviceController extends Controller
{
    public function live(): JsonResponse
    {
        $devices = RfidDevice::query()->orderBy('id')->get()->map(fn (RfidDevice $device): array => [
            'id' => $device->id,
            'online' => $device->isOnline(),
            'is_active' => $device->is_active,
            'last_seen' => $device->last_seen_at?->diffForHumans() ?? 'Belum pernah terlihat di server',
            'last_heartbeat' => $device->last_heartbeat_at?->diffForHumans() ?? 'Belum tercatat',
        ]);

        return response()->json([
            'devices' => $devices,
            'online_count' => $devices->where('online', true)->count(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function index(): View
    {
        return view('settings.rfid-devices.index', [
            'devices' => RfidDevice::query()->latest('last_seen_at')->orderBy('name')->get(),
            'types' => RfidDeviceType::cases(),
        ]);
    }

    public function store(StoreRfidDeviceRequest $request, RfidDeviceService $service): RedirectResponse
    {
        $result = $service->create($request->validated(), $request->user());

        return back()->with('status', 'Perangkat RFID berhasil didaftarkan. Salin token sekarang karena token tidak dapat ditampilkan kembali.')->with('device_token', $result['token'])->with('device_id', $result['device']->device_id);
    }

    public function rotateToken(RfidDevice $device, RfidDeviceService $service): RedirectResponse
    {
        $token = $service->rotateToken($device, request()->user());

        return back()->with('status', 'Token perangkat berhasil diganti. Token lama langsung tidak berlaku.')->with('device_token', $token)->with('device_id', $device->device_id);
    }

    public function toggle(RfidDevice $device, RfidDeviceService $service): RedirectResponse
    {
        $service->toggle($device, request()->user());

        return back()->with('status', $device->is_active ? 'Perangkat RFID diaktifkan.' : 'Perangkat RFID dinonaktifkan.');
    }
}
