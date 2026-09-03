<x-layouts.app :title="$title" :breadcrumbs="['Beranda', 'Profil Saya']">
    <div class="space-y-5">
        <x-ui.page-header title="Profil Saya" description="Informasi akun dan data personalia Anda." />

        <div class="grid gap-5 lg:grid-cols-2">
            <x-ui.card>
                <h2 class="font-bold text-emerald-950">Informasi Akun</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Nama akun</dt><dd class="font-semibold">{{ $user->name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Role</dt><dd class="font-semibold">{{ $user->display_role }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Username</dt><dd class="font-semibold">{{ $user->username }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Email akun</dt><dd class="font-semibold">{{ $user->email }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Status akun</dt><dd class="font-semibold text-emerald-700">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Login terakhir</dt><dd class="font-semibold">{{ $user->last_login_at?->translatedFormat('d F Y, H:i') ?? 'Belum pernah' }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <h2 class="font-bold text-emerald-950">Informasi Personalia</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Nama lengkap</dt><dd class="font-semibold">{{ $personnel->full_name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Jabatan</dt><dd class="font-semibold">{{ $personnel->position ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">NIY</dt><dd class="font-semibold">{{ $personnel->foundation_employee_number ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">NIP</dt><dd class="font-semibold">{{ $personnel->nip ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Nomor HP</dt><dd class="font-semibold">{{ $personnel->phone ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Email personalia</dt><dd class="font-semibold">{{ $personnel->email ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Status kepegawaian</dt><dd class="font-semibold">{{ $personnel->employment_status_label }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Status data</dt><dd class="font-semibold text-emerald-700">{{ $personnel->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                </dl>
            </x-ui.card>
        </div>

        <x-ui.card>
            <div class="grid gap-5 md:grid-cols-[180px_1fr]">
                @if($personnel->faceProfile)
                    <img class="h-40 w-full rounded-xl object-cover" src="{{ route('personnel.profile.face.photo') }}" alt="Foto acuan wajah Anda">
                @else
                    <div class="flex h-40 items-center justify-center rounded-xl bg-slate-100 px-4 text-center text-sm text-slate-500">Belum ada foto acuan</div>
                @endif
                <div>
                    <h2 class="font-bold text-emerald-950">Face Recognition</h2>
                    @if($personnel->faceProfile)
                        <p class="mt-2 font-semibold text-emerald-700">Wajah telah terdaftar</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $personnel->faceProfile->samples->count() }} sampel wajah tersimpan. Terakhir didaftarkan {{ $personnel->faceProfile->registered_at?->translatedFormat('d F Y, H:i') }}.</p>
                    @else
                        <p class="mt-2 font-semibold text-slate-700">Wajah belum terdaftar</p>
                        <p class="mt-1 text-sm text-slate-600">Daftarkan wajah agar akun Anda dapat menggunakan absensi Face Recognition.</p>
                    @endif
                    @if($personnel->is_active)
                        <button id="face-enrollment-open" type="button" class="btn btn-primary mt-4">{{ $personnel->faceProfile ? 'Daftarkan Ulang Wajah' : 'Daftarkan Wajah' }}</button>
                    @else
                        <p class="mt-4 text-sm text-rose-700">Pendaftaran wajah tidak tersedia karena data personalia Anda nonaktif.</p>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>

    @if($personnel->is_active)
        <dialog id="face-enrollment" class="m-auto w-full max-w-xl rounded-2xl p-6 backdrop:bg-slate-950/60">
            <form method="post" enctype="multipart/form-data" action="{{ route('personnel.profile.face.enroll') }}">
                @csrf
                <h2 class="text-lg font-bold text-emerald-950">Pendaftaran Wajah Saya</h2>
                <p class="mt-2 text-sm text-slate-600">Pastikan pencahayaan cukup, lepaskan masker atau kacamata gelap, dan pastikan hanya wajah Anda yang terlihat.</p>
                <video id="face-video" class="mt-4 aspect-video w-full rounded-xl bg-slate-900" autoplay muted playsinline></video>
                <canvas id="face-canvas" class="hidden"></canvas>
                <p id="face-camera-error" class="my-3 hidden rounded-xl bg-red-50 p-3 text-sm text-red-700" role="alert"></p>
                <p id="face-step" class="my-3 font-semibold">Foto 1: wajah menghadap depan</p>
                @foreach(['front', 'left', 'right'] as $pose)<input type="file" name="{{ $pose }}" accept="image/jpeg,image/png" hidden required>@endforeach
                <div class="flex flex-wrap gap-2"><button id="face-capture" type="button" class="btn btn-primary" disabled>Ambil Foto</button><button type="button" class="btn btn-secondary" data-face-close>Batal</button><button id="face-submit" class="btn btn-primary hidden">Validasi &amp; Simpan</button></div>
            </form>
        </dialog>
        @push('scripts')
            <script>
                (() => {
                    const dialog = document.getElementById('face-enrollment'), open = document.getElementById('face-enrollment-open'), video = document.getElementById('face-video'), canvas = document.getElementById('face-canvas'), capture = document.getElementById('face-capture'), submit = document.getElementById('face-submit'), step = document.getElementById('face-step'), error = document.getElementById('face-camera-error');
                    const poses = ['front', 'left', 'right'], directions = ['depan', 'sedikit ke kiri', 'sedikit ke kanan']; let current = 0;
                    const stop = () => { video?.srcObject?.getTracks().forEach(track => track.stop()); if (video) video.srcObject = null; };
                    const fail = message => { error.textContent = message; error.classList.remove('hidden'); capture.disabled = true; };
                    open?.addEventListener('click', async () => { current = 0; dialog.querySelectorAll('input[type="file"]').forEach(input => input.value = ''); step.textContent = 'Foto 1: wajah menghadap depan'; capture.classList.remove('hidden'); submit.classList.add('hidden'); capture.disabled = true; dialog.showModal(); error.classList.add('hidden'); if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(location.hostname)) return fail('Kamera hanya dapat digunakan melalui koneksi HTTPS.'); if (!navigator.mediaDevices?.getUserMedia) return fail('Peramban ini tidak mendukung akses kamera.'); try { const stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'user'}, audio: false}); video.srcObject = stream; await video.play(); capture.disabled = false; } catch (exception) { fail(exception.name === 'NotAllowedError' ? 'Izin kamera ditolak. Izinkan akses kamera lalu coba lagi.' : 'Kamera tidak dapat dibuka. Pastikan kamera tersedia.'); } });
                    dialog?.querySelector('[data-face-close]')?.addEventListener('click', () => dialog.close()); dialog?.addEventListener('close', stop);
                    capture?.addEventListener('click', () => { if (!video.videoWidth || !video.videoHeight) return fail('Kamera belum siap. Tunggu beberapa saat lalu coba lagi.'); canvas.width = video.videoWidth; canvas.height = video.videoHeight; canvas.getContext('2d').drawImage(video, 0, 0); canvas.toBlob(blob => { if (!blob) return fail('Foto gagal diambil. Silakan coba lagi.'); const input = dialog.querySelector(`[name="${poses[current]}"]`), transfer = new DataTransfer(); transfer.items.add(new File([blob], `${poses[current]}.jpg`, {type: 'image/jpeg'})); input.files = transfer.files; current++; if (current === 3) { capture.classList.add('hidden'); submit.classList.remove('hidden'); step.textContent = 'Tiga foto siap divalidasi dan disimpan.'; stop(); } else step.textContent = `Foto ${current + 1}: wajah ${directions[current]}`; }, 'image/jpeg', .92); });
                })();
            </script>
        @endpush
    @endif
</x-layouts.app>
