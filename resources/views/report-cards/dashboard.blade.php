<x-module-page title="Dashboard Rapor" subtitle="Ringkasan penyusunan, verifikasi, penguncian, dan kelengkapan nilai rapor siswa.">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            'Rapor Draft' => $metrics['draft'],
            'Menunggu Persetujuan' => $metrics['submitted'],
            'Approved' => $metrics['approved'],
            'Locked' => $metrics['locked'],
            'Kelengkapan Nilai' => $metrics['gradeCompleteness'],
            'Kelas Belum Generate' => $metrics['classesWithoutReports'],
        ] as $label => $value)
            <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-black text-emerald-950">{{ number_format($value) }}</p></div>
        @endforeach
    </div>
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl bg-white p-6 shadow-sm xl:col-span-2"><h2 class="text-lg font-bold text-emerald-950">Aktivitas Rapor Terbaru</h2><div class="mt-4 space-y-3">@forelse($latestCards as $card)<div class="rounded-xl border border-slate-100 p-4 text-sm"><div class="flex flex-wrap justify-between gap-2"><span class="font-semibold text-emerald-950">{{ $card->student?->name ?? 'Siswa #'.$card->student_id }}</span><span class="rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-800">{{ str($card->status)->headline() }}</span></div><p class="mt-1 text-slate-500">{{ $card->classroom?->name ?? 'Kelas belum tersedia' }} · {{ $card->updated_at?->diffForHumans() }}</p></div>@empty<div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Belum ada aktivitas rapor.</div>@endforelse</div></section>
        <section class="rounded-2xl bg-white p-6 shadow-sm"><h2 class="text-lg font-bold text-emerald-950">Shortcut Workflow</h2><div class="mt-4 space-y-3">@can('report-cards.view-class')<a href="{{ route('report-cards.classes') }}" class="block rounded-xl bg-emerald-900 px-4 py-3 text-center font-semibold text-white">Penyusunan Rapor</a>@endcan @can('report-cards.approve')<a href="{{ route('report-cards.verification') }}" class="block rounded-xl border border-emerald-800 px-4 py-3 text-center font-semibold text-emerald-900">Verifikasi Rapor</a>@endcan</div></section>
    </div>
</x-module-page>
