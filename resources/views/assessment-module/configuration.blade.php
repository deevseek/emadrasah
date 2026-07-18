<x-app-layout :title="$title ?? 'Konfigurasi Penilaian'">
<div class="space-y-6">@include('assessment-module._tabs')
<x-ui.page-header title="Konfigurasi Penilaian" description="Atur skema, komponen, KKM, dan periode penilaian yang digunakan dalam rapor." />
<x-ui.card><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
@can('assessments.manage-components')<x-ui.button :href="route('assessments.components')">Tambah Komponen Penilaian</x-ui.button>@endcan
@can('assessments.manage-minimum-criteria')<x-ui.button variant="outline" :href="route('assessments.minimum-criteria')">Atur KKM</x-ui.button>@endcan
@can('assessments.manage-periods')<x-ui.button variant="outline" :href="route('assessments.periods')">Buat Periode Penilaian</x-ui.button>@endcan
<x-ui.button variant="secondary" :href="route('assessments.index')">Lihat Ringkasan</x-ui.button>
</div><div class="mt-6"><x-ui.empty-state title="Konfigurasi siap dikelola" description="Pilih tindakan yang tersedia sesuai permission untuk melengkapi alur operasional penilaian." /></div></x-ui.card>
</div>
</x-app-layout>
