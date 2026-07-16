<x-app-layout title="Absensi Saya">
    <div class="mb-6">
        <p class="text-sm text-slate-500">Absensi / Absensi Saya</p>
        <h1 class="text-2xl font-semibold text-emerald-950">Absensi Saya</h1>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card>
            <div class="text-center">
                <p class="text-sm text-slate-500">Jam realtime</p>
                <p id="clock" class="text-3xl font-bold text-emerald-950">--:--:--</p>
                <p class="mt-2 text-sm">Status hari ini: <strong>{{ $today?->status?->label() ?? 'Belum absen' }}</strong></p>
            </div>

            @if ($today === null)
                <form class="mt-6 space-y-4" method="post" action="{{ route('employee-attendances.check-in') }}" enctype="multipart/form-data">
                    @csrf
                    @foreach (['latitude' => 'Latitude', 'longitude' => 'Longitude', 'accuracy' => 'Akurasi GPS (meter)'] as $name => $label)
                        <label class="block text-sm font-medium text-slate-700">
                            {{ $label }}
                            <input class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="{{ $name }}" value="{{ old($name) }}" readonly>
                            @error($name)<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                    <button id="gps-button" type="button" class="rounded-lg border border-emerald-900 px-4 py-2 text-emerald-900 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">Ambil GPS</button>
                    <p id="gps-status" class="text-sm text-slate-500">GPS belum diambil.</p>
                    <label class="block text-sm font-medium text-slate-700">
                        Selfie
                        <input id="selfie" class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" type="file" name="selfie" accept="image/*">
                        @error('selfie')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <img id="selfie-preview" class="hidden h-32 rounded-lg object-cover" alt="Preview selfie">
                    <label class="block text-sm font-medium text-slate-700">
                        Catatan
                        <textarea class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="notes">{{ old('notes') }}</textarea>
                    </label>
                    <button class="w-full rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Check-in</button>
                </form>
            @elseif ($today->checked_out_at === null)
                <form class="mt-6" method="post" action="{{ route('employee-attendances.check-out') }}">
                    @csrf
                    <button class="w-full rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Check-out</button>
                </form>
            @endif
        </x-ui.card>

        <x-ui.card class="lg:col-span-2">
            <h2 class="font-semibold text-emerald-950">Riwayat Pribadi</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead><tr><th class="px-3 py-2 text-left">Tanggal</th><th class="px-3 py-2 text-left">Masuk</th><th class="px-3 py-2 text-left">Pulang</th><th class="px-3 py-2 text-left">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($records as $record)
                            <tr><td class="px-3 py-2">{{ $record->attendance_date->format('d/m/Y') }}</td><td class="px-3 py-2">{{ $record->checked_in_at?->format('H:i') ?? '-' }}</td><td class="px-3 py-2">{{ $record->checked_out_at?->format('H:i') ?? '-' }}</td><td class="px-3 py-2">{{ $record->status->label() }}</td></tr>
                        @empty
                            <tr><td class="px-3 py-8 text-center text-slate-500" colspan="4">Belum ada riwayat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $records->links() }}</div>
        </x-ui.card>
    </div>

    <script>
        setInterval(() => document.getElementById('clock').textContent = new Date().toLocaleTimeString('id-ID'), 1000);
        document.getElementById('gps-button')?.addEventListener('click', () => navigator.geolocation.getCurrentPosition((position) => {
            document.querySelector('[name=latitude]').value = position.coords.latitude;
            document.querySelector('[name=longitude]').value = position.coords.longitude;
            document.querySelector('[name=accuracy]').value = Math.round(position.coords.accuracy);
            document.getElementById('gps-status').textContent = 'GPS berhasil diambil.';
        }, () => document.getElementById('gps-status').textContent = 'Izin GPS ditolak atau tidak tersedia.'));
        document.getElementById('selfie')?.addEventListener('change', (event) => {
            const [file] = event.target.files;
            if (file) {
                document.getElementById('selfie-preview').src = URL.createObjectURL(file);
                document.getElementById('selfie-preview').classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
