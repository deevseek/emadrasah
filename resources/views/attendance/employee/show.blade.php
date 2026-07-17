<x-app-layout title="Detail Kehadiran Pegawai">
    <div class="mb-6"><p class="text-sm text-slate-500">Kehadiran / Detail</p><h1 class="text-2xl font-semibold text-emerald-950">Detail Kehadiran Pegawai</h1></div>
    @if (session('status'))<div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>@endif
    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card><dl class="grid gap-4 md:grid-cols-2 text-sm">
            <div><dt class="text-slate-500">Pegawai</dt><dd class="font-medium">{{ $record->employee->name }}</dd></div>
            <div><dt class="text-slate-500">Tanggal</dt><dd>{{ $record->attendance_date->format('d/m/Y') }}</dd></div>
            <div><dt class="text-slate-500">Jadwal Kerja</dt><dd>{{ $record->workSchedule?->name ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd>{{ $record->status->label() }}</dd></div>
            <div><dt class="text-slate-500">Check-in</dt><dd>{{ $record->checked_in_at?->format('H:i') ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Check-out</dt><dd>{{ $record->checked_out_at?->format('H:i') ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Terlambat</dt><dd>{{ $record->late_minutes }} menit</dd></div>
            <div><dt class="text-slate-500">Pulang Lebih Awal</dt><dd>{{ $record->early_leave_minutes }} menit</dd></div>
            <div><dt class="text-slate-500">Lokasi Masuk</dt><dd>{{ $record->check_in_latitude ?? $record->latitude ?? '-' }}, {{ $record->check_in_longitude ?? $record->longitude ?? '-' }}</dd></div>
            <div><dt class="text-slate-500">Verifikasi</dt><dd>{{ $record->verification_status?->label() }}</dd></div>
        </dl></x-ui.card>
        <x-ui.card><h2 class="font-semibold text-emerald-950">Verifikasi</h2><form class="mt-4 space-y-4" method="post" action="{{ route('employee-attendances.verify', $record) }}">@csrf @method('patch')
            <label class="block text-sm font-medium text-slate-700">Status Verifikasi<select class="mt-1 w-full rounded-lg border-slate-300" name="verification_status">@foreach ($verifications as $verification)<option value="{{ $verification->value }}" @selected(old('verification_status', $record->verification_status?->value) === $verification->value)>{{ $verification->label() }}</option>@endforeach</select></label>
            <label class="block text-sm font-medium text-slate-700">Catatan Verifikasi<textarea class="mt-1 w-full rounded-lg border-slate-300" name="verification_notes">{{ old('verification_notes', $record->verification_notes) }}</textarea></label>
            <button class="rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white">Simpan Verifikasi</button></form></x-ui.card>
        <x-ui.card class="lg:col-span-2"><h2 class="font-semibold text-emerald-950">Koreksi Kehadiran</h2><form class="mt-4 grid gap-4 md:grid-cols-2" method="post" action="{{ route('employee-attendances.correct', $record) }}">@csrf @method('patch')
            <label class="block text-sm font-medium text-slate-700">Check-in<input type="datetime-local" name="checked_in_at" value="{{ old('checked_in_at', $record->checked_in_at?->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label class="block text-sm font-medium text-slate-700">Check-out<input type="datetime-local" name="checked_out_at" value="{{ old('checked_out_at', $record->checked_out_at?->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label class="block text-sm font-medium text-slate-700">Status<select name="status" class="mt-1 w-full rounded-lg border-slate-300">@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $record->status->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
            <label class="block text-sm font-medium text-slate-700">Catatan<input name="notes" value="{{ old('notes', $record->notes) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label class="block text-sm font-medium text-slate-700 md:col-span-2">Alasan Koreksi<textarea required name="reason" class="mt-1 w-full rounded-lg border-slate-300">{{ old('reason') }}</textarea></label>
            <button class="rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white md:col-span-2">Simpan Koreksi</button></form></x-ui.card>
    </div>
</x-app-layout>
