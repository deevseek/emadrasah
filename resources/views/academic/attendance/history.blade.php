<x-app-layout title="Riwayat Absensi">
    <div class="space-y-6">
        <x-ui.page-header
            title="Riwayat Absensi"
            description="Telusuri catatan absensi berdasarkan tanggal dan rombel."
        />

        <form class="card card-body grid gap-4 md:grid-cols-3">
            <label>
                <span class="label">Rombel</span>
                <select class="input mt-1" name="classroom_id">
                    <option value="">Semua rombel</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected(request('classroom_id') == $room->id)>
                            {{ $room->display_name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="label">Tanggal absensi</span>
                <input class="input mt-1" type="date" name="attendance_date" value="{{ request('attendance_date') }}">
            </label>
            <button class="btn btn-primary self-end">Tampilkan</button>
        </form>

        <div class="hidden md:block">
            <div class="table-wrap">
                <table class="data-table min-w-[800px]">
                    <colgroup>
                        <col class="w-36">
                        <col class="w-72">
                        <col class="w-80">
                        <col class="w-28">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Rombel</th>
                            <th>Siswa</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $row)
                            <tr>
                                <td class="whitespace-nowrap font-medium">{{ $row->attendance_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $row->classroom->display_name }}</td>
                                <td class="font-semibold text-slate-900">{{ $row->student->full_name }}</td>
                                <td>
                                    <span @class([
                                        'badge',
                                        'badge-success' => $row->status->value === 'present',
                                        'badge-warning' => $row->status->value === 'sick',
                                        'badge-info' => $row->status->value === 'permitted',
                                        'badge-danger' => $row->status->value === 'absent',
                                    ])>{{ $row->status->label() }}</span>
                                </td>
                                <td>{{ $row->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-500">
                                    Belum ada data absensi sesuai filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card divide-y divide-slate-100 md:hidden">
            @forelse($records as $row)
                <article class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <strong class="block text-slate-900">{{ $row->student->full_name }}</strong>
                            <span class="mt-1 block text-sm text-slate-500">{{ $row->classroom->display_name }}</span>
                        </div>
                        <span @class([
                            'badge shrink-0',
                            'badge-success' => $row->status->value === 'present',
                            'badge-warning' => $row->status->value === 'sick',
                            'badge-info' => $row->status->value === 'permitted',
                            'badge-danger' => $row->status->value === 'absent',
                        ])>{{ $row->status->label() }}</span>
                    </div>
                    <dl class="grid grid-cols-[7rem_1fr] gap-x-3 gap-y-1 text-sm">
                        <dt class="text-slate-500">Tanggal</dt>
                        <dd class="font-medium text-slate-700">{{ $row->attendance_date->translatedFormat('d M Y') }}</dd>
                        <dt class="text-slate-500">Catatan</dt>
                        <dd class="text-slate-700">{{ $row->notes ?: '—' }}</dd>
                    </dl>
                </article>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">
                    Belum ada data absensi sesuai filter yang dipilih.
                </p>
            @endforelse
        </div>

        {{ $records->links() }}
    </div>
</x-app-layout>
