<x-module-page title="Dashboard BTAQ" subtitle="Ringkasan operasional BTAQ berdasarkan jurnal, kelompok, dan perkembangan siswa yang tercatat.">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            'Jurnal Hari Ini' => $metrics['today'],
            'Jurnal Draft' => $metrics['draft'],
            'Menunggu Verifikasi' => $metrics['pending'],
            'Kelompok Aktif' => $metrics['activeGroups'],
            'Siswa Aktif' => $metrics['activeStudents'],
            'Siswa Perlu Bimbingan' => $metrics['guidance'],
        ] as $label => $value)
            <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-3xl font-black text-emerald-950">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl bg-white p-6 shadow-sm xl:col-span-2">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="text-lg font-bold text-emerald-950">Perkembangan Terbaru</h2><p class="text-sm text-slate-500">Catatan perkembangan terakhir dari jurnal BTAQ.</p></div>
                <a href="{{ route('btaq.progress') }}" class="rounded-lg border border-emerald-800 px-4 py-2 text-sm font-semibold text-emerald-900">Lihat perkembangan</a>
            </div>
            @forelse ($latestProgress as $progress)
                <div class="border-t py-3 text-sm"><span class="font-semibold text-emerald-950">Siswa #{{ $progress->student_id }}</span><span class="ml-2 rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-800">{{ str($progress->progress_status)->headline() }}</span><p class="mt-1 text-slate-500">{{ $progress->achievement_notes ?: 'Belum ada catatan capaian.' }}</p></div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Belum ada perkembangan BTAQ yang tercatat.</div>
            @endforelse
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-emerald-950">Shortcut Workflow</h2>
            <div class="mt-4 space-y-3">
                @can('btaq-journals.create')<a href="{{ route('btaq-journals.create') }}" class="block rounded-xl bg-emerald-900 px-4 py-3 text-center font-semibold text-white">Input Jurnal BTAQ</a>@endcan
                @can('btaq-journals.verify')<a href="{{ route('btaq-journals.index') }}" class="block rounded-xl border border-emerald-800 px-4 py-3 text-center font-semibold text-emerald-900">Verifikasi Jurnal</a>@endcan
            </div>
        </section>
    </div>
</x-module-page>
