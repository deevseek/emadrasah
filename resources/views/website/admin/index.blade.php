<x-layouts.app title="Kelola Website">
    <x-ui.page-header title="Kelola Website" description="Pilih bagian yang ingin diperbarui. Perubahan konten dapat dilihat melalui pratinjau sebelum dipublikasikan.">
        <x-slot:actions><x-ui.button variant="outline" :href="route('website.preview')" target="_blank">Buka pratinjau</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach([
            ['type' => 'news', 'title' => 'Berita dan kegiatan', 'description' => 'Tulis kabar terbaru, simpan sebagai draf, lalu terbitkan.', 'count' => $counts['published'].' terbit · '.$counts['draft'].' draf'],
            ['type' => 'program', 'title' => 'Program unggulan', 'description' => 'Perkenalkan program utama yang ditawarkan madrasah.', 'count' => $counts['program'].' konten'],
            ['type' => 'facility', 'title' => 'Fasilitas', 'description' => 'Tampilkan sarana dan prasarana yang tersedia.', 'count' => $counts['facility'].' konten'],
            ['type' => 'achievement', 'title' => 'Prestasi', 'description' => 'Bagikan pencapaian peserta didik dan madrasah.', 'count' => $counts['achievement'].' konten'],
            ['type' => 'testimonial', 'title' => 'Testimoni', 'description' => 'Kelola cerita singkat dari orang tua atau alumni.', 'count' => $counts['testimonial'].' aktif'],
        ] as $section)
            <x-ui.card class="transition hover:border-emerald-200 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="text-lg font-bold text-emerald-950">{{ $section['title'] }}</h2><span class="badge badge-muted mt-2">{{ $section['count'] }}</span></div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-lg font-bold text-emerald-700" aria-hidden="true">→</span>
                </div>
                <p class="mt-4 min-h-10 text-sm leading-6 text-slate-600">{{ $section['description'] }}</p>
                <div class="mt-5 flex gap-2">
                    <x-ui.button :href="route('website.content.index', $section['type'])">Kelola</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('website.content.create', $section['type'])">Tambah baru</x-ui.button>
                </div>
            </x-ui.card>
        @endforeach

        <x-ui.card class="border-emerald-800 bg-emerald-950 text-white">
            <h2 class="text-lg font-bold">Tampilan dan informasi utama</h2>
            <p class="mt-4 min-h-10 text-sm leading-6 text-emerald-100">Atur judul halaman depan, profil singkat, PPDB, kontak, dan publikasi website.</p>
            <div class="mt-5"><x-ui.button class="bg-white text-emerald-950 hover:bg-slate-100" :href="route('website.settings')">Atur tampilan</x-ui.button></div>
        </x-ui.card>
    </div>
</x-layouts.app>
