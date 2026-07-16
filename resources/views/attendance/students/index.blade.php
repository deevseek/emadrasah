<x-app-layout title="Absensi Siswa">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-sm text-slate-500">Absensi / Siswa</p><h1 class="text-2xl font-semibold text-emerald-950">Absensi Siswa</h1></div>
        <a class="rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700" href="{{ route('student-attendances.create') }}">Input Massal</a>
    </div>
    @if (session('status'))<div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>@endif
    <x-ui.card>
        <form class="mb-6 grid gap-4 md:grid-cols-3">
            <label class="block text-sm font-medium text-slate-700">Kelas<select class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="classroom_id"><option value="">Semua kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></label>
            <label class="block text-sm font-medium text-slate-700">Tanggal<input class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" type="date" name="date" value="{{ request('date') }}"></label>
            <button class="self-end rounded-lg bg-emerald-900 px-4 py-2 text-white hover:bg-emerald-800">Filter</button>
        </form>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr><th class="px-3 py-2 text-left">Tanggal</th><th class="px-3 py-2 text-left">Kelas</th><th class="px-3 py-2 text-left">Siswa</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Catatan</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($records as $record)<tr><td class="px-3 py-2">{{ $record->attendance_date->format('d/m/Y') }}</td><td class="px-3 py-2">{{ $record->classroom->name }}</td><td class="px-3 py-2">{{ $record->student->name }}</td><td class="px-3 py-2"><x-ui.badge>{{ $record->status->label() }}</x-ui.badge></td><td class="px-3 py-2">{{ $record->notes ?? '-' }}</td></tr>@empty<tr><td class="px-3 py-8 text-center text-slate-500" colspan="5">Belum ada absensi siswa.</td></tr>@endforelse</tbody></table></div>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-ui.card>
</x-app-layout>
