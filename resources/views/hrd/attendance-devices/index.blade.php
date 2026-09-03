<x-layouts.app title="Perangkat Absensi" :breadcrumbs="['HRD', 'Perangkat Absensi']">
<div class="index-stack">
    <x-ui.page-header title="Manajemen Perangkat Absensi" description="Daftarkan, setujui, atau cabut perangkat yang digunakan personalia untuk absensi dengan pengenalan wajah." />

    <x-ui.alert type="info"><strong>Alur keamanan:</strong> UUID hanya disimpan sebagai hash. Perangkat baru yang belum didaftarkan akan masuk sebagai permintaan tertunda dan tidak dapat melanjutkan verifikasi wajah sebelum disetujui HRD.</x-ui.alert>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_23rem]">
        <x-ui.card>
            <form method="get" class="mb-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]">
                <div><label for="search">Cari personalia</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Nama personalia"></div>
                <div><label for="status">Status perangkat</label><select id="status" name="status"><option value="">Semua status</option><option value="pending" @selected(request('status')==='pending')>Menunggu persetujuan</option><option value="trusted" @selected(request('status')==='trusted')>Disetujui</option><option value="revoked" @selected(request('status')==='revoked')>Dicabut</option></select></div>
                <button class="btn btn-secondary self-end" type="submit">Terapkan</button>
            </form>

            @if($devices->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Belum ada perangkat absensi yang sesuai.</div>
            @else
                <div class="table-wrap"><table class="data-table min-w-[850px]"><thead><tr><th>Personalia</th><th>Perangkat</th><th>Aktivitas</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @foreach($devices as $device)
                    <tr>
                        <td><strong class="text-emerald-950">{{ $device->personnel->full_name }}</strong><p class="text-xs text-slate-500">{{ $device->personnel->position }}</p></td>
                        <td><strong>{{ $device->device_name ?: 'Perangkat pribadi' }}</strong><p class="text-xs text-slate-500">{{ collect([$device->platform, $device->browser])->filter()->join(' · ') ?: 'Informasi perangkat tidak tersedia' }}</p><p class="mt-1 font-mono text-xs text-slate-400">{{ substr($device->device_uuid_hash, 0, 12) }}…</p></td>
                        <td class="text-sm"><p>Pertama: {{ $device->first_seen_at->translatedFormat('d M Y, H:i') }}</p><p>Terakhir: {{ $device->last_seen_at->diffForHumans() }}</p></td>
                        <td>@if($device->revoked_at)<span class="badge badge-danger">Dicabut</span>@elseif($device->is_trusted)<span class="badge badge-success">Disetujui</span>@else<span class="badge badge-warning">Menunggu</span>@endif @if($device->trustedBy)<p class="mt-1 text-xs text-slate-500">oleh {{ $device->trustedBy->name }}</p>@endif</td>
                        <td>@if(!$device->is_trusted && !$device->revoked_at)<form method="post" action="{{ route('hrd.attendance-devices.approve',$device) }}">@csrf @method('PATCH')<button class="btn btn-primary" type="submit">Setujui</button></form>@elseif($device->is_trusted)<form method="post" action="{{ route('hrd.attendance-devices.revoke',$device) }}" onsubmit="return confirm('Cabut akses perangkat ini?')">@csrf @method('PATCH')<button class="btn btn-danger" type="submit">Cabut</button></form>@else<form method="post" action="{{ route('hrd.attendance-devices.approve',$device) }}">@csrf @method('PATCH')<button class="btn btn-secondary" type="submit">Aktifkan Kembali</button></form>@endif</td>
                    </tr>
                @endforeach
                </tbody></table></div>
                <div class="mt-4">{{ $devices->links() }}</div>
            @endif
        </x-ui.card>

        <x-ui.card><h2 class="text-lg font-bold text-emerald-950">Daftarkan Perangkat</h2><p class="mb-4 text-sm text-slate-500">Masukkan UUID dari perangkat milik personalia. Perangkat langsung disetujui setelah disimpan.</p>
            <form method="post" action="{{ route('hrd.attendance-devices.store') }}" class="space-y-4">@csrf
                <div><label for="personnel_id">Personalia</label><select id="personnel_id" name="personnel_id" required><option value="">Pilih personalia</option>@foreach($personnel as $person)<option value="{{ $person->id }}" @selected(old('personnel_id')==$person->id)>{{ $person->full_name }} — {{ $person->position }}</option>@endforeach</select>@error('personnel_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="device_uuid">UUID Perangkat</label><input id="device_uuid" name="device_uuid" required value="{{ old('device_uuid') }}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"><p class="mt-1 text-xs text-slate-500">Guru dapat menyalin UUID yang ditampilkan saat perangkat belum disetujui.</p>@error('device_uuid')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="device_name">Nama Perangkat</label><input id="device_name" name="device_name" required maxlength="100" value="{{ old('device_name') }}" placeholder="Ponsel Guru Ahmad"></div>
                <div><label for="platform">Platform</label><input id="platform" name="platform" maxlength="80" value="{{ old('platform') }}" placeholder="Android, iOS, Windows"></div>
                <div><label for="browser">Browser</label><input id="browser" name="browser" maxlength="80" value="{{ old('browser') }}" placeholder="Chrome, Safari"></div>
                <button class="btn btn-primary w-full" type="submit">Daftarkan Perangkat</button>
            </form>
        </x-ui.card>
    </div>
</div>
</x-layouts.app>
