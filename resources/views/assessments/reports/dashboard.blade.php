<x-module-page title="Dashboard Penilaian" subtitle="Pantau kesiapan komponen, kelengkapan input nilai, dan kebutuhan remedial akademik.">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            'Komponen Draft' => $metrics['draft'],
            'Komponen Published' => $metrics['published'],
            'Nilai Belum Lengkap' => $metrics['incompleteComponents'],
            'Siswa Belum Dinilai' => $metrics['unscoredStudents'],
            'Siswa di Bawah KKM' => $metrics['belowKkm'],
            'Remedial' => $metrics['remedial'],
        ] as $label => $value)
            <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-black text-emerald-950">{{ number_format($value) }}</p></div>
        @endforeach
    </div>
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl bg-white p-6 shadow-sm xl:col-span-2"><h2 class="text-lg font-bold text-emerald-950">Progress Nilai per Kelas</h2><div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left">Kelas</th><th class="px-3 py-2 text-left">Siswa</th><th class="px-3 py-2 text-left">Nilai Masuk</th></tr></thead><tbody>@forelse($classProgress as $row)<tr class="border-t"><td class="px-3 py-3 font-semibold">{{ $row->name }}</td><td class="px-3 py-3">{{ $row->students_count }}</td><td class="px-3 py-3"><span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-800">{{ $row->scores_count }}</span></td></tr>@empty<tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">Belum ada data kelas untuk penilaian.</td></tr>@endforelse</tbody></table></div></section>
        <section class="rounded-2xl bg-white p-6 shadow-sm"><h2 class="text-lg font-bold text-emerald-950">Shortcut Workflow</h2><div class="mt-4 space-y-3">@can('assessments.create')<a href="{{ route('assessment-components.create') }}" class="block rounded-xl bg-emerald-900 px-4 py-3 text-center font-semibold text-white">Buat Komponen</a>@endcan @can('assessments.update')<a href="{{ route('assessment-components.index') }}" class="block rounded-xl border border-emerald-800 px-4 py-3 text-center font-semibold text-emerald-900">Input Nilai</a>@endcan</div></section>
    </div>
</x-module-page>
