<x-app-layout :title="$title ?? 'Penilaian & Rapor'">
@include('assessment-module._tabs')
<section class="card"><div class="card-body"><h2 class="text-lg font-bold text-emerald-950">{{ $title ?? 'Penilaian & Rapor' }}</h2><p class="mt-2 text-sm text-slate-600">Halaman operasional {{ $title ?? 'Penilaian & Rapor' }} berbasis data madrasah terkini. Gunakan filter, validasi, dan alur persetujuan sesuai kewenangan.</p><div class="mt-4 empty-state">Belum ada data yang sesuai filter. Tambahkan atau pilih periode aktif untuk memulai.</div></div></section>
</x-app-layout>
