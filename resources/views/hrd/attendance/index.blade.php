<x-layouts.app :title="$title" :breadcrumbs="['HRD', 'Absensi Pegawai']">
    <div class="space-y-4">
        <x-ui.card>
            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label>
                    <span>Status kehadiran</span>
                    <select class="mt-1" name="status">
                        <option value="">Semua status</option>
                        @foreach(\App\Enums\Hrd\AttendanceStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Shift kerja</span>
                    <select class="mt-1" name="shift_number">
                        <option value="">Semua shift</option>
                        @foreach([1, 2, 3] as $shift)
                            <option value="{{ $shift }}" @selected((string) request('shift_number') === (string) $shift)>Shift {{ $shift }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Metode absensi</span>
                    <select class="mt-1" name="method">
                        <option value="">Semua metode</option>
                        @foreach(['self' => 'Mandiri', 'manual' => 'Manual', 'face' => 'Wajah'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="btn btn-primary self-end">Terapkan filter</button>
            </form>
        </x-ui.card>

        <div class="hidden md:block">
            <div class="table-wrap">
                <table class="data-table min-w-[960px]">
                    <thead>
                        <tr>
                            @foreach(['Nama', 'Tanggal', 'Shift', 'Masuk', 'Pulang', 'Foto Absensi', 'Status', 'Terlambat', 'Metode'] as $heading)
                                <th>{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            @php($photoEvents = $attendance->audits->filter(fn ($audit) => $audit->face_verified && $audit->faceVerification?->snapshot_path)->keyBy('event'))
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $attendance->personnel->full_name }}</td>
                                <td class="whitespace-nowrap">{{ $attendance->attendance_date->translatedFormat('d M Y') }}</td>
                                <td>Shift {{ $attendance->shift_number }}</td>
                                <td class="font-medium">{{ $attendance->check_in_time?->format('H:i') ?? '—' }}</td>
                                <td class="font-medium">{{ $attendance->check_out_time?->format('H:i') ?? '—' }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        @foreach(['check_in' => 'Masuk', 'check_out' => 'Pulang'] as $event => $label)
                                            @if($photoEvents->has($event))
                                                <a href="{{ route('hrd.attendance.photo', [$attendance, $event]) }}" target="_blank" rel="noopener" class="group text-center" title="Lihat foto {{ strtolower($label) }} {{ $attendance->personnel->full_name }}">
                                                    <img src="{{ route('hrd.attendance.photo', [$attendance, $event]) }}" alt="Foto {{ strtolower($label) }}" class="h-12 w-12 rounded-lg border border-emerald-900/10 object-cover group-hover:ring-2 group-hover:ring-amber-500">
                                                    <span class="mt-1 block text-[10px] text-slate-500">{{ $label }}</span>
                                                </a>
                                            @endif
                                        @endforeach
                                        @if($photoEvents->isEmpty())<span class="text-slate-400">—</span>@endif
                                    </div>
                                </td>
                                <td>
                                    <span @class([
                                        'badge',
                                        'badge-success' => $attendance->status->value === 'hadir',
                                        'badge-warning' => in_array($attendance->status->value, ['terlambat', 'izin', 'sakit', 'cuti', 'dinas_luar'], true),
                                        'badge-danger' => $attendance->status->value === 'alpha',
                                    ])>{{ $attendance->status->label() }}</span>
                                </td>
                                <td class="whitespace-nowrap">{{ $attendance->late_minutes }} menit</td>
                                <td>{{ ['self' => 'Mandiri', 'manual' => 'Manual', 'face' => 'Wajah'][$attendance->method] ?? ucfirst($attendance->method) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-10 text-center text-slate-500">Belum ada data absensi sesuai filter yang dipilih.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card divide-y divide-slate-100 md:hidden">
            @forelse($attendances as $attendance)
                @php($photoEvents = $attendance->audits->filter(fn ($audit) => $audit->face_verified && $audit->faceVerification?->snapshot_path)->keyBy('event'))
                <article class="space-y-4 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="break-words font-bold leading-5 text-emerald-950">{{ $attendance->personnel->full_name }}</h2>
                            <p class="mt-1 text-xs font-medium text-slate-500">{{ $attendance->attendance_date->translatedFormat('d F Y') }} · Shift {{ $attendance->shift_number }}</p>
                        </div>
                        <span @class([
                            'badge shrink-0',
                            'badge-success' => $attendance->status->value === 'hadir',
                            'badge-warning' => in_array($attendance->status->value, ['terlambat', 'izin', 'sakit', 'cuti', 'dinas_luar'], true),
                            'badge-danger' => $attendance->status->value === 'alpha',
                        ])>{{ $attendance->status->label() }}</span>
                    </div>

                    <dl class="grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center">
                        <div><dt class="text-[11px] font-medium text-slate-500">Masuk</dt><dd class="mt-1 font-bold text-slate-800">{{ $attendance->check_in_time?->format('H:i') ?? '—' }}</dd></div>
                        <div class="border-x border-slate-200"><dt class="text-[11px] font-medium text-slate-500">Pulang</dt><dd class="mt-1 font-bold text-slate-800">{{ $attendance->check_out_time?->format('H:i') ?? '—' }}</dd></div>
                        <div><dt class="text-[11px] font-medium text-slate-500">Terlambat</dt><dd class="mt-1 font-bold text-slate-800">{{ $attendance->late_minutes }} mnt</dd></div>
                    </dl>

                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-medium text-slate-500">Metode</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-700">{{ ['self' => 'Mandiri', 'manual' => 'Manual', 'face' => 'Wajah'][$attendance->method] ?? ucfirst($attendance->method) }}</p>
                        </div>
                        @if($photoEvents->isNotEmpty())
                            <div class="flex gap-2" aria-label="Foto absensi">
                                @foreach(['check_in' => 'Masuk', 'check_out' => 'Pulang'] as $event => $label)
                                    @if($photoEvents->has($event))
                                        <a href="{{ route('hrd.attendance.photo', [$attendance, $event]) }}" target="_blank" rel="noopener" class="text-center" aria-label="Lihat foto {{ strtolower($label) }} {{ $attendance->personnel->full_name }}">
                                            <img src="{{ route('hrd.attendance.photo', [$attendance, $event]) }}" alt="Foto {{ strtolower($label) }}" class="h-11 w-11 rounded-full border-2 border-white object-cover shadow ring-1 ring-emerald-900/10">
                                            <span class="mt-1 block text-[10px] font-medium text-slate-500">{{ $label }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">Belum ada data absensi sesuai filter yang dipilih.</p>
            @endforelse
        </div>

        {{ $attendances->links() }}
    </div>
</x-layouts.app>
