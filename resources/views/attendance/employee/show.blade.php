<x-app-layout title="Detail Absensi Guru">
    <div class="mb-6">
        <p class="text-sm text-slate-500">Absensi / Detail Guru</p>
        <h1 class="text-2xl font-semibold text-emerald-950">Detail Absensi Guru</h1>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>
    @endif

    <x-ui.card>
        <dl class="grid gap-4 md:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Pegawai</dt><dd class="font-medium">{{ $record->employee->name }}</dd></div>
            <div><dt class="text-sm text-slate-500">Status</dt><dd><x-ui.badge>{{ $record->status->label() }}</x-ui.badge></dd></div>
            <div><dt class="text-sm text-slate-500">Tanggal</dt><dd>{{ $record->attendance_date->format('d/m/Y') }}</dd></div>
            <div><dt class="text-sm text-slate-500">Lokasi</dt><dd>{{ $record->latitude ?? '-' }}, {{ $record->longitude ?? '-' }} ({{ $record->accuracy ?? '-' }}m)</dd></div>
        </dl>

        @if ($record->selfie_path)
            <img class="mt-4 h-44 rounded-lg object-cover" src="{{ Storage::url($record->selfie_path) }}" alt="Selfie absensi">
        @endif

        <form class="mt-6 space-y-4" method="post" action="{{ route('employee-attendances.verify', $record) }}">
            @csrf
            @method('patch')
            <label class="block text-sm font-medium text-slate-700">
                Status Koreksi
                <select class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="status">
                    @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $record->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('status')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm font-medium text-slate-700">
                Alasan Koreksi
                <textarea class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="correction_reason" required>{{ old('correction_reason') }}</textarea>
                @error('correction_reason')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <button class="rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Verifikasi / Koreksi</button>
        </form>
    </x-ui.card>
</x-app-layout>
