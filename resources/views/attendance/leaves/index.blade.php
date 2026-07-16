<x-app-layout title="Perizinan Guru">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">Absensi / Perizinan Guru</p>
            <h1 class="text-2xl font-semibold text-emerald-950">Perizinan Guru</h1>
        </div>
        <a class="rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700" href="{{ route('employee-leaves.create') }}">Ajukan Izin</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>
    @endif

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead><tr><th class="px-3 py-2 text-left">Pegawai</th><th class="px-3 py-2 text-left">Tanggal</th><th class="px-3 py-2 text-left">Jenis</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($leaves as $leave)
                        <tr><td class="px-3 py-2">{{ $leave->employee->name }}</td><td class="px-3 py-2">{{ $leave->starts_at->format('d/m/Y') }} - {{ $leave->ends_at->format('d/m/Y') }}</td><td class="px-3 py-2">{{ $leave->type->value }}</td><td class="px-3 py-2"><x-ui.badge>{{ $leave->status->value }}</x-ui.badge></td><td class="px-3 py-2"><a class="font-medium text-emerald-800 hover:text-emerald-700" href="{{ route('employee-leaves.show', $leave) }}">Detail</a></td></tr>
                    @empty
                        <tr><td class="px-3 py-8 text-center text-slate-500" colspan="5">Belum ada pengajuan izin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $leaves->links() }}</div>
    </x-ui.card>
</x-app-layout>
