<x-app-layout title="Kehadiran Pegawai">
    <div class="mb-6">
        <p class="text-sm text-slate-500">Kehadiran / Rekap Pegawai</p>
        <h1 class="text-2xl font-semibold text-emerald-950">Kehadiran Pegawai</h1>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>
    @endif

    <x-ui.card>
        <form class="grid gap-4 md:grid-cols-5">
            <label class="block text-sm font-medium text-slate-700">
                Tanggal
                <input class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" type="date" name="date" value="{{ request('date') }}">
            </label>
            <label class="block text-sm font-medium text-slate-700">
                Bulan
                <input class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" type="month" name="month" value="{{ request('month') }}">
            </label>
            <label class="block text-sm font-medium text-slate-700">
                Pegawai
                <select class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="employee_id">
                    <option value="">Semua pegawai</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium text-slate-700">
                Status
                <select class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="status">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </label>
            <button class="self-end rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Filter</button>
        </form>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Pegawai</th>
                        <th class="px-3 py-2">Masuk</th>
                        <th class="px-3 py-2">Pulang</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-3 py-2">{{ $record->attendance_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $record->employee->name }}</td>
                            <td class="px-3 py-2">{{ $record->checked_in_at?->format('H:i') ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $record->checked_out_at?->format('H:i') ?? '-' }}</td>
                            <td class="px-3 py-2"><x-ui.badge>{{ $record->status->label() }}</x-ui.badge></td>
                            <td class="px-3 py-2"><a class="font-medium text-emerald-800 hover:text-emerald-700 focus:underline" href="{{ route('employee-attendances.show', $record) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td class="px-3 py-8 text-center text-slate-500" colspan="6">Belum ada data absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-app-layout>
