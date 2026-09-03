<x-layouts.app title="Perangkat Absensi Saya" :breadcrumbs="['HRD', 'Perangkat Absensi Saya']">
<div class="index-stack">
    <x-ui.page-header title="Perangkat Absensi Saya" description="Daftarkan perangkat melalui akun guru Anda, lalu tunggu validasi Operator atau Super Admin." />

    <x-ui.alert type="info"><strong>Alur validasi:</strong> perangkat yang baru didaftarkan berstatus menunggu dan belum dapat digunakan untuk absensi. UUID disimpan sebagai hash untuk melindungi identitas perangkat.</x-ui.alert>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_23rem]">
        <x-ui.card>
            <h2 class="mb-4 text-lg font-bold text-emerald-950">Daftar Perangkat {{ $personnel->full_name }}</h2>
            @if($devices->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Anda belum mendaftarkan perangkat absensi.</div>
            @else
                <div class="table-wrap"><table class="data-table"><thead><tr><th>Perangkat</th><th>Diajukan</th><th>Status</th></tr></thead><tbody>
                @foreach($devices as $device)
                    <tr>
                        <td><strong class="text-emerald-950">{{ $device->device_name }}</strong><p class="text-xs text-slate-500">{{ collect([$device->platform, $device->browser])->filter()->join(' · ') ?: 'Informasi perangkat tidak tersedia' }}</p></td>
                        <td>{{ $device->created_at->translatedFormat('d M Y, H:i') }}</td>
                        <td>@if($device->revoked_at)<span class="badge badge-danger">Dicabut</span>@elseif($device->is_trusted)<span class="badge badge-success">Disetujui</span>@else<span class="badge badge-warning">Menunggu validasi</span>@endif</td>
                    </tr>
                @endforeach
                </tbody></table></div>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-bold text-emerald-950">Daftarkan Perangkat Ini</h2>
            <p class="mb-4 text-sm text-slate-500">Identitas perangkat diisi otomatis oleh browser dan hanya didaftarkan untuk akun Anda.</p>
            <form method="post" action="{{ route('hrd.attendance-devices.store') }}" class="space-y-4" data-device-form>@csrf
                <input type="hidden" id="device_uuid" name="device_uuid" value="{{ old('device_uuid') }}">
                <div><label for="device_name">Nama Perangkat</label><input id="device_name" name="device_name" required maxlength="100" value="{{ old('device_name') }}" placeholder="Ponsel utama saya">@error('device_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="platform">Platform</label><input id="platform" name="platform" maxlength="80" value="{{ old('platform') }}" readonly></div>
                <div><label for="browser">Browser</label><input id="browser" name="browser" maxlength="80" value="{{ old('browser') }}" readonly></div>
                @error('device_uuid')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="btn btn-primary w-full" type="submit">Ajukan Validasi Perangkat</button>
            </form>
        </x-ui.card>
    </div>
</div>
<script>
(() => {
    const form = document.querySelector('[data-device-form]');
    if (!form) return;
    let uuid = localStorage.getItem('attendance_device_uuid');
    if (!uuid) { uuid = crypto.randomUUID(); localStorage.setItem('attendance_device_uuid', uuid); }
    document.querySelector('#device_uuid').value = uuid;
    document.querySelector('#platform').value ||= navigator.userAgentData?.platform || navigator.platform || 'Tidak diketahui';
    document.querySelector('#browser').value ||= navigator.userAgentData?.brands?.[0]?.brand || 'Browser';
})();
</script>
</x-layouts.app>
