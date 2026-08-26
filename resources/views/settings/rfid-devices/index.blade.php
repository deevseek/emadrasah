<x-layouts.app title="Perangkat RFID" :breadcrumbs="['Pengaturan', 'Perangkat RFID']">
<div class="index-stack">
    <x-ui.page-header title="Manajemen Perangkat RFID" description="Daftarkan ESP32 RFID dan pantau koneksi berdasarkan heartbeat terakhir." />
    @if(session('device_token'))<x-ui.alert type="warning"><p class="font-bold">Token untuk {{ session('device_id') }} hanya ditampilkan satu kali.</p><p class="mt-1">Salin ke konstanta <code>DEVICE_TOKEN</code> pada firmware, lalu simpan secara aman.</p><div class="mt-3 flex gap-2"><input class="font-mono" readonly value="{{ session('device_token') }}" aria-label="Token perangkat"><button class="btn btn-secondary" type="button" data-copy-token>Salin Token</button></div></x-ui.alert>@endif
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <x-ui.card>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold text-emerald-950">Daftar Perangkat</h2><p class="text-sm text-slate-500">Online bila heartbeat diterima dalam 75 detik terakhir.</p></div><span class="badge badge-success">{{ $devices->filter->isOnline()->count() }} terhubung</span></div>
            @if($devices->isEmpty())<div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Belum ada perangkat RFID yang didaftarkan.</div>@else
            <div class="overflow-x-auto"><table><thead><tr><th>Perangkat</th><th>Tipe</th><th>Koneksi</th><th>Telemetri</th><th>Aksi</th></tr></thead><tbody>
            @foreach($devices as $device)<tr>
                <td><strong class="text-emerald-950">{{ $device->name }}</strong><p class="font-mono text-xs text-slate-500">{{ $device->device_id }}</p>@unless($device->is_active)<span class="badge badge-danger mt-1">Dinonaktifkan</span>@endunless</td>
                <td>{{ $device->device_type->label() }}<p class="text-xs text-slate-500">Mode: {{ $device->mode ?: 'belum diketahui' }}</p></td>
                <td><span class="badge {{ $device->isOnline() ? 'badge-success' : 'badge-danger' }}">{{ $device->isOnline() ? 'Terhubung' : 'Terputus' }}</span><p class="mt-1 text-xs text-slate-500">{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah terhubung' }}</p></td>
                <td class="text-sm"><p>Firmware: {{ $device->firmware_version ?: '—' }}</p><p>IP: {{ $device->ip_address ?: '—' }}</p><p>RSSI: {{ $device->rssi !== null ? $device->rssi.' dBm' : '—' }}</p></td>
                <td><div class="flex flex-col gap-2"><form method="post" action="{{ route('rfid-devices.toggle', $device) }}">@csrf @method('PATCH')<button class="btn btn-secondary w-full" type="submit">{{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form><form method="post" action="{{ route('rfid-devices.rotate-token', $device) }}" onsubmit="return confirm('Token lama akan langsung tidak berlaku. Lanjutkan?')">@csrf<button class="btn btn-secondary w-full" type="submit">Ganti Token</button></form></div></td>
            </tr>@endforeach
            </tbody></table></div>@endif
        </x-ui.card>
        <x-ui.card><h2 class="text-lg font-bold text-emerald-950">Tambah Perangkat</h2><p class="mb-4 text-sm text-slate-500">Token rahasia dibuat otomatis dan hanya disimpan dalam bentuk hash.</p>
            <form method="post" action="{{ route('rfid-devices.store') }}" class="space-y-4">@csrf
                <div><label for="device_id">ID Perangkat</label><input id="device_id" name="device_id" required maxlength="100" pattern="[a-z0-9][a-z0-9_-]*" value="{{ old('device_id') }}" placeholder="rfid-reader-01"><p class="mt-1 text-xs text-slate-500">Harus sama dengan <code>DEVICE_ID</code> pada firmware.</p></div>
                <div><label for="name">Nama Perangkat</label><input id="name" name="name" required maxlength="150" value="{{ old('name') }}" placeholder="Reader Gerbang Utama"></div>
                <div><label for="device_type">Tipe Perangkat</label><select id="device_type" name="device_type" required>@foreach($types as $type)<option value="{{ $type->value }}" @selected(old('device_type') === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
                <button class="btn btn-primary w-full" type="submit">Daftarkan Perangkat</button>
            </form>
        </x-ui.card>
    </div>
    <x-ui.alert type="info"><strong>Integrasi firmware:</strong> kirim <code>POST /api/rfid/device/heartbeat</code> setiap 30 detik dengan header <code>X-Device-ID</code> dan <code>X-Device-Token</code>. Reader absensi juga wajib mengirim kedua header tersebut.</x-ui.alert>
</div>
<script>document.querySelector('[data-copy-token]')?.addEventListener('click',async event=>{const input=event.currentTarget.previousElementSibling;await navigator.clipboard.writeText(input.value);event.currentTarget.textContent='Tersalin';});</script>
</x-layouts.app>
