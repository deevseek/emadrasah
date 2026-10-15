<x-layouts.app title="Perangkat RFID" :breadcrumbs="['Pengaturan', 'Perangkat RFID']">
<div class="index-stack">
    <x-ui.page-header title="Manajemen Perangkat RFID" description="Daftarkan perangkat RFID dan pantau status koneksi secara otomatis." />
    @if(session('device_token'))<x-ui.alert type="warning"><p class="font-bold">Token untuk {{ session('device_id') }} hanya ditampilkan satu kali.</p><p class="mt-1">Salin ke konstanta <code>DEVICE_TOKEN</code> pada firmware, lalu simpan secara aman.</p><div class="mt-3 flex gap-2"><input class="font-mono" readonly value="{{ session('device_token') }}" aria-label="Token perangkat"><button class="btn btn-secondary" type="button" data-copy-token>Salin Token</button></div></x-ui.alert>@endif
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <x-ui.card>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold text-emerald-950">Daftar Perangkat</h2><p class="text-sm text-slate-500">Status diperbarui setiap 5 detik. Terputus jika tidak ada komunikasi dengan server selama lebih dari 75 detik.</p></div><div><span class="badge badge-success" data-online-count>{{ $devices->filter->isOnline()->count() }} terhubung</span><p class="mt-1 text-xs text-slate-500" data-live-status role="status">Menyiapkan pembaruan otomatis…</p></div></div>
            @if($devices->isEmpty())<div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Belum ada perangkat RFID yang didaftarkan.</div>@else
            <div class="overflow-x-auto"><table><thead><tr><th>Perangkat</th><th>Tipe</th><th>Koneksi</th><th>Telemetri</th><th>Aksi</th></tr></thead><tbody>
            @foreach($devices as $device)<tr data-device-id="{{ $device->id }}">
                <td><strong class="text-emerald-950">{{ $device->name }}</strong><p class="font-mono text-xs text-slate-500">{{ $device->device_id }}</p>@unless($device->is_active)<span class="badge badge-danger mt-1">Dinonaktifkan</span>@endunless</td>
                <td>{{ $device->device_type->label() }}<p class="text-xs text-slate-500">Mode: {{ $device->mode ?: 'belum diketahui' }}</p></td>
                <td><span data-device-connection class="badge {{ $device->isOnline() ? 'badge-success' : 'badge-danger' }}">{{ ! $device->is_active ? 'Dinonaktifkan' : ($device->isOnline() ? 'Terhubung' : ($device->last_seen_at ? 'Terputus' : 'Belum terhubung')) }}</span><p data-device-last-seen class="mt-1 text-xs text-slate-500">{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah terlihat di server' }}</p><p class="mt-1 text-xs text-slate-500">Heartbeat valid: <span data-device-heartbeat>{{ $device->last_heartbeat_at?->diffForHumans() ?? 'Belum tercatat' }}</span></p></td>
                <td class="text-sm"><p>Firmware: {{ $device->firmware_version ?: '—' }}</p><p>IP: {{ $device->ip_address ?: '—' }}</p><p>RSSI: {{ $device->rssi !== null ? $device->rssi.' dBm' : '—' }}</p><p>MAC: {{ $device->mac_address ?: '—' }}</p><p>BSSID: {{ $device->wifi_bssid ?: '—' }}</p><p>Gateway/subnet: {{ $device->wifi_gateway ?: '—' }} / {{ $device->wifi_subnet ?: '—' }}</p><p>Kanal: {{ $device->wifi_channel ?? '—' }}; heap: {{ $device->free_heap ?? '—' }}</p><p>Putus WiFi dilaporkan: {{ $device->wifi_disconnect_count ?? '—' }}; reason: {{ $device->wifi_disconnect_reason ?? '—' }}</p></td>
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
<script>
(() => {
    const status = document.querySelector('[data-live-status]');
    let timer;
    let busy = false;
    async function poll() {
        if (busy || document.hidden) return;
        busy = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(@json(route('rfid-devices.live', [], false)), {
                headers: {Accept: 'application/json'}, credentials: 'same-origin',
                cache: 'no-store', signal: controller.signal,
            });
            if (!response.ok) throw new Error('Pembaruan gagal');
            const data = await response.json();
            if (!Array.isArray(data.devices) || typeof data.online_count !== 'number') throw new Error('Data tidak valid');
            data.devices.forEach(device => {
                const row = document.querySelector(`[data-device-id="${Number(device.id)}"]`);
                if (!row) return;
                const badge = row.querySelector('[data-device-connection]');
                badge.textContent = !device.is_active ? 'Dinonaktifkan' : (device.online ? 'Terhubung' : (device.last_seen === 'Belum pernah terlihat di server' ? 'Belum terhubung' : 'Terputus'));
                badge.className = `badge ${device.online ? 'badge-success' : 'badge-danger'}`;
                row.querySelector('[data-device-last-seen]').textContent = device.last_seen;
                row.querySelector('[data-device-heartbeat]').textContent = device.last_heartbeat;
            });
            document.querySelector('[data-online-count]').textContent = `${data.online_count} terhubung`;
            status.textContent = `Terakhir diperbarui: ${new Date().toLocaleTimeString('id-ID')}`;
        } catch (error) {
            status.textContent = 'Pembaruan terputus. Status terakhir belum terverifikasi; mencoba kembali…';
            document.querySelectorAll('[data-device-connection]').forEach(badge => {
                badge.textContent = 'Status belum terverifikasi';
                badge.className = 'badge badge-warning';
            });
            document.querySelector('[data-online-count]').textContent = 'Koneksi pemantauan terputus';
        } finally {
            clearTimeout(timeout);
            busy = false;
            clearTimeout(timer);
            timer = setTimeout(poll, 5000);
        }
    }
    document.addEventListener('visibilitychange', () => {
        clearTimeout(timer);
        if (!document.hidden) poll();
    });
    poll();
})();
</script>
</x-layouts.app>
