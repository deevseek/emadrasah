<x-layouts.app :title="$title" :breadcrumbs="['Beranda','Kesiswaan','Data Siswa',$student->full_name]"><div class="space-y-5"><x-ui.page-header :title="$student->full_name" :description="$student->age_label"><div class="flex gap-2">@can('students.update')<a class="btn btn-primary" href="{{ route('students.edit',$student) }}">Edit</a>@endcan @can('students.change-status')<a class="btn btn-secondary" href="{{ route('students.status.edit',$student) }}">Ubah Status</a>@endcan<a class="btn btn-secondary" href="{{ route('students.index') }}">Kembali</a></div></x-ui.page-header><x-ui.card><div class="grid gap-4 md:grid-cols-2">@foreach(['gender_label'=>'Jenis Kelamin','nisn'=>'NISN','nik'=>'NIK','birth_place'=>'Tempat Lahir','birth_date'=>'Tanggal Lahir','classroom_label'=>'Tingkat/Rombel','status_label'=>'Status','address'=>'Alamat','phone'=>'Nomor Telepon','special_needs'=>'Kebutuhan Khusus','disability'=>'Disabilitas','kip_pip_number'=>'Nomor KIP/PIP','father_name'=>'Nama Ayah','mother_name'=>'Nama Ibu','guardian_name'=>'Nama Wali'] as $key=>$label)<div><p class="text-xs text-slate-500">{{ $label }}</p><p class="font-semibold">@if($key==='birth_date'){{ $student->birth_date?->translatedFormat('d F Y')?:'—' }}@elseif(in_array($key,['nik','address','phone','kip_pip_number'])){{ $display[$key]?:'—' }}@elseif(in_array($key,['special_needs','disability'])){{ $student->$key?:'Tidak Ada' }}@else{{ $student->$key?:'—' }}@endif</p></div>@endforeach</div></x-ui.card><section class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-bold text-emerald-950">Kartu RFID</h2>
            @if($student->activeRfidCard)
                <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <dt class="text-slate-500">Status</dt><dd class="font-semibold text-emerald-700">Aktif</dd>
                    <dt class="text-slate-500">UID</dt><dd>{{ $student->activeRfidCard->maskedUid() }}</dd>
                    <dt class="text-slate-500">Diterbitkan</dt><dd>{{ $student->activeRfidCard->registered_at?->translatedFormat('d M Y H:i') }}</dd>
                    <dt class="text-slate-500">Terakhir digunakan</dt><dd>{{ $student->activeRfidCard->last_used_at?->diffForHumans() ?? 'Belum pernah' }}</dd>
                </dl>
            @else
                <p class="mt-1 text-sm text-slate-500">Belum mempunyai kartu.</p>
            @endif
            <div class="mt-4 border-t border-slate-100 pt-3">
                <h3 class="text-xs font-bold uppercase tracking-wide text-emerald-900">Absensi Hari Ini</h3>
                @if($todayAttendance)
                    <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                        <dt class="text-slate-500">Status</dt><dd class="font-semibold">{{ $todayAttendance->status->label() }}</dd>
                        <dt class="text-slate-500">Sumber</dt><dd>{{ $todayAttendance->source->value === 'rfid' ? 'RFID' : 'Manual' }}</dd>
                        @if($todayAttendance->source->value === 'rfid')<dt class="text-slate-500">Waktu Scan</dt><dd>{{ $todayAttendance->scanned_at?->format('H:i') ?? '—' }}</dd>@endif
                        <dt class="text-slate-500">Rombel</dt><dd>{{ $todayAttendance->classroom?->display_name ?? '—' }}</dd>
                    </dl>
                @else<p class="mt-1 text-sm text-slate-500">Belum melakukan absensi.</p>@endif
            </div>
            <p class="mt-2 text-xs font-semibold {{ $writerOnline ? 'text-emerald-700' : 'text-red-600' }}">Writer {{ $writerOnline ? 'Online' : 'Offline' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('rfid-card.issue')
                <button class="btn btn-primary" type="button" data-rfid-write data-replace="{{ $student->activeRfidCard ? '1' : '0' }}" @disabled(!$writerEnabled || !$writerOnline)>{{ $student->activeRfidCard ? 'Tulis Ulang' : 'Tulis Kartu RFID' }}</button>
            @endcan
            @if($student->activeRfidCard)
                @can('rfid-card.replace')<button class="btn btn-secondary" type="button" data-rfid-write data-replace="1" @disabled(!$writerEnabled || !$writerOnline)>Ganti Kartu</button>@endcan
                @can('rfid-card.disable')<form method="post" action="{{ route('students.rfid-card.destroy',$student) }}">@csrf @method('DELETE')<button class="btn btn-danger">Nonaktifkan</button></form>@endcan
            @endif
        </div>
    </div>

    <dialog id="rfid-writer-dialog" class="subject-dialog">
        <div class="dialog-panel">
            <div class="dialog-heading"><h2>Penulisan Kartu RFID</h2><button type="button" data-rfid-close aria-label="Tutup">&times;</button></div>
            <p class="text-sm" data-rfid-message></p>
            <div class="progress hidden" data-rfid-progress><span class="w-1/2 animate-pulse"></span></div>
            <dl class="hidden text-sm" data-rfid-result>
                <dt class="text-slate-500">Nama siswa</dt><dd class="font-semibold">{{ $student->full_name }}</dd>
                <dt class="mt-2 text-slate-500">NIS</dt><dd>{{ $student->nis ?? $student->nisn ?? '—' }}</dd>
                <dt class="mt-2 text-slate-500">ID Reader</dt><dd data-rfid-device>—</dd>
                <dt class="mt-2 text-slate-500">Waktu penulisan</dt><dd data-rfid-completed>—</dd>
            </dl>
            <div class="dialog-actions"><button type="button" class="btn btn-secondary" data-rfid-close>Tutup</button></div>
        </div>
    </dialog>
</section><x-ui.card><div class="grid gap-4 md:grid-cols-2"><div>Dibuat oleh: {{ $student->createdBy?->name?:'—' }}<br>{{ $student->created_at?->translatedFormat('d F Y H:i') }}</div><div>Diperbarui oleh: {{ $student->updatedBy?->name?:'—' }}<br>{{ $student->updated_at?->translatedFormat('d F Y H:i') }}</div></div></x-ui.card></div><script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('rfid-writer-dialog');
    if (! dialog) return;

    const message = dialog.querySelector('[data-rfid-message]');
    const progress = dialog.querySelector('[data-rfid-progress]');
    const result = dialog.querySelector('[data-rfid-result]');
    const device = dialog.querySelector('[data-rfid-device]');
    const completed = dialog.querySelector('[data-rfid-completed]');
    // Gunakan URL relatif agar permintaan tetap memakai origin/protokol halaman.
    // Ini penting ketika Laravel berada di balik reverse proxy HTTPS.
    const writerUrl = @json(route('students.rfid-writer.store', $student, false));
    let timer = null;
    let reloadAfterClose = false;

    const close = () => {
        window.clearInterval(timer);
        timer = null;
        dialog.close();
        if (reloadAfterClose) window.location.reload();
    };

    const finish = (data) => {
        window.clearInterval(timer);
        timer = null;
        progress.classList.add('hidden');
        reloadAfterClose = data.status === 'completed';

        if (data.status === 'completed') {
            message.textContent = 'Kartu RFID berhasil ditulis';
            device.textContent = data.device;
            completed.textContent = data.completed_at ? new Date(data.completed_at).toLocaleString('id-ID') : '—';
            result.classList.remove('hidden');
            return;
        }

        const failures = {
            CARD_REMOVED: 'Kartu dilepas sebelum proses selesai.',
            VERIFY_FAILED: 'Verifikasi data kartu gagal.',
            DEVICE_ERROR: 'RFID Writer tidak terhubung.',
        };
        message.textContent = data.status === 'expired'
            ? 'Waktu penulisan kartu habis. Silakan coba kembali.'
            : (failures[data.error_code] ?? 'Kartu tidak dapat ditulis.');
    };

    const poll = (commandId) => {
        timer = window.setInterval(async () => {
            try {
                const response = await fetch(`${writerUrl}/${commandId}`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (! response.ok) throw new Error(data.message ?? 'Status penulisan kartu tidak dapat diperiksa.');
                if (['completed', 'failed', 'expired'].includes(data.status)) finish(data);
            } catch (error) {
                window.clearInterval(timer);
                timer = null;
                progress.classList.add('hidden');
                message.textContent = error instanceof TypeError
                    ? 'Server aplikasi tidak dapat dihubungi. Periksa koneksi lalu coba kembali.'
                    : error.message;
            }
        }, 1500);
    };

    document.querySelectorAll('[data-rfid-write]').forEach((button) => {
        button.addEventListener('click', async () => {
            reloadAfterClose = false;
            result.classList.add('hidden');
            progress.classList.remove('hidden');
            message.textContent = 'Mempersiapkan RFID Writer...';
            dialog.showModal();

            try {
                const response = await fetch(writerUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ replace: button.dataset.replace === '1' }),
                });
                const data = await response.json();
                if (! response.ok) throw new Error(data.message ?? Object.values(data.errors ?? {})[0]?.[0] ?? 'RFID Writer tidak terhubung.');
                message.textContent = 'Tempelkan kartu RFID pada reader';
                poll(data.command_id);
            } catch (error) {
                progress.classList.add('hidden');
                message.textContent = error instanceof TypeError
                    ? 'Server aplikasi tidak dapat dihubungi. Periksa koneksi lalu coba kembali.'
                    : error.message;
            }
        });
    });

    dialog.querySelectorAll('[data-rfid-close]').forEach((button) => button.addEventListener('click', close));
    dialog.addEventListener('cancel', (event) => { event.preventDefault(); close(); });
});
</script></x-layouts.app>
