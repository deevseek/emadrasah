<x-app-layout title="Input Absensi Siswa">
    <div class="mb-6"><p class="text-sm text-slate-500">Absensi / Siswa</p><h1 class="text-2xl font-semibold text-emerald-950">Input Absensi Siswa</h1></div>
    <x-ui.card>
        <form class="mb-6 grid gap-4 md:grid-cols-3">
            <label class="block text-sm font-medium text-slate-700">Kelas<select class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="classroom_id">@foreach ($classrooms as $item)<option value="{{ $item->id }}" @selected($classroom?->id === $item->id)>{{ $item->name }}</option>@endforeach</select></label>
            <label class="block text-sm font-medium text-slate-700">Tanggal<input class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" type="date" name="attendance_date" value="{{ request('attendance_date', now()->toDateString()) }}"></label>
            <button class="self-end rounded-lg border border-emerald-900 px-4 py-2 text-emerald-900 hover:bg-emerald-50">Muat Siswa</button>
        </form>
        @if ($classroom)
            <form method="post" action="{{ route('student-attendances.store') }}">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                <input type="hidden" name="attendance_date" value="{{ request('attendance_date', now()->toDateString()) }}">
                <button type="button" onclick="document.querySelectorAll('select.status').forEach((select) => select.value = 'hadir')" class="mb-3 rounded-lg bg-emerald-100 px-3 py-2 text-emerald-950 hover:bg-emerald-200">Tandai Semua Hadir</button>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr><th class="px-3 py-2 text-left">Siswa</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Catatan</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($enrollments as $enrollment)<tr><td class="px-3 py-2">{{ $enrollment->student->name }}</td><td class="px-3 py-2"><select class="status rounded-lg border-slate-300" name="students[{{ $enrollment->student_id }}][status]">@foreach (\App\Enums\AttendanceStatus::cases() as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></td><td class="px-3 py-2"><input class="w-full rounded-lg border-slate-300" name="students[{{ $enrollment->student_id }}][notes]"></td></tr>@empty<tr><td class="px-3 py-8 text-center text-slate-500" colspan="3">Kelas belum memiliki enrollment aktif.</td></tr>@endforelse</tbody></table></div>
                <button class="mt-4 rounded-lg bg-emerald-900 px-4 py-2 text-white hover:bg-emerald-800">Simpan Absensi</button>
            </form>
        @endif
    </x-ui.card>
</x-app-layout>
