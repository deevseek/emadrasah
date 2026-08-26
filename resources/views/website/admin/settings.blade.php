<x-layouts.app title="Tampilan Website">
    <x-ui.page-header title="Tampilan dan Informasi Website" description="Atur satu bagian dalam satu waktu agar perubahan lebih mudah diperiksa.">
        <x-slot:actions><x-ui.button variant="secondary" :href="route('website.index')">Kembali</x-ui.button><x-ui.button variant="outline" :href="route('website.preview')" target="_blank">Buka pratinjau</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    @php($groups = [
        'hero' => ['label' => 'Halaman depan', 'description' => 'Judul dan ajakan utama'],
        'about' => ['label' => 'Tentang madrasah', 'description' => 'Profil singkat madrasah'],
        'ppdb' => ['label' => 'Informasi PPDB', 'description' => 'Ajakan pendaftaran siswa'],
        'contact' => ['label' => 'Kontak', 'description' => 'Kontak, media sosial, dan peta'],
        'publication' => ['label' => 'Publikasi', 'description' => 'Aktifkan website publik'],
        'seo' => ['label' => 'Pengaturan pencarian', 'description' => 'Informasi untuk mesin pencari'],
    ])
    <div class="mt-6 grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
        <nav class="space-y-2" aria-label="Bagian pengaturan website">
            @foreach($groups as $key => $section)
                <a href="{{ route('website.settings', ['group' => $key]) }}" @class(['block rounded-xl border p-4 transition', 'border-emerald-700 bg-emerald-950 text-white shadow-sm' => $group === $key, 'border-slate-200 bg-white text-slate-700 hover:border-emerald-200' => $group !== $key])>
                    <strong class="block text-sm">{{ $section['label'] }}</strong><span @class(['mt-1 block text-xs', 'text-emerald-100' => $group === $key, 'text-slate-500' => $group !== $key])>{{ $section['description'] }}</span>
                </a>
            @endforeach
        </nav>

        <x-ui.card>
            <h2 class="text-xl font-bold text-emerald-950">{{ $groups[$group]['label'] }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $groups[$group]['description'] }}. Simpan sebelum berpindah ke bagian lain.</p>
            <form method="post" action="{{ route('website.settings.update') }}" enctype="multipart/form-data" class="mt-6 grid gap-5">
                @csrf @method('PUT')<input type="hidden" name="group" value="{{ $group }}">
                @php($fields = match($group) {
                    'hero' => ['hero_badge' => ['Subjudul singkat', 'text'], 'hero_title' => ['Judul utama', 'text'], 'hero_description' => ['Deskripsi', 'textarea'], 'hero_cta_primary_label' => ['Teks tombol utama', 'text'], 'hero_cta_primary_url' => ['Tautan tombol utama', 'url'], 'hero_cta_secondary_label' => ['Teks tombol kedua', 'text'], 'hero_cta_secondary_url' => ['Tautan tombol kedua', 'url']],
                    'about' => ['about_label' => ['Subjudul', 'text'], 'about_title' => ['Judul', 'text'], 'about_description' => ['Deskripsi madrasah', 'textarea']],
                    'ppdb' => ['ppdb_label' => ['Tahun ajaran', 'text'], 'ppdb_title' => ['Judul', 'text'], 'ppdb_description' => ['Deskripsi', 'textarea'], 'ppdb_button_label' => ['Teks tombol pendaftaran', 'text'], 'ppdb_button_url' => ['Tautan pendaftaran', 'url']],
                    'contact' => ['footer_description' => ['Deskripsi singkat', 'textarea'], 'operating_hours' => ['Jam pelayanan', 'text'], 'social_facebook' => ['Tautan Facebook', 'url'], 'social_instagram' => ['Tautan Instagram', 'url'], 'social_youtube' => ['Tautan YouTube', 'url'], 'social_tiktok' => ['Tautan TikTok', 'url'], 'google_maps_url' => ['Tautan Google Maps', 'url'], 'google_maps_embed' => ['Tautan sematan Google Maps', 'url']],
                    'seo' => ['site_title' => ['Judul website', 'text'], 'meta_description' => ['Deskripsi pencarian', 'textarea'], 'meta_keywords' => ['Kata kunci', 'text'], 'og_title' => ['Judul saat dibagikan', 'text'], 'og_description' => ['Deskripsi saat dibagikan', 'textarea']],
                    default => []
                })
                @if($group === 'publication')
                    <label class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4"><input type="hidden" name="values[landing_enabled]" value="0"><input class="mt-0.5 h-4 w-4" type="checkbox" name="values[landing_enabled]" value="1" @checked(old('values.landing_enabled', $settings['landing_enabled'] ?? '0') === '1')><span><strong class="block text-emerald-950">Website publik aktif</strong><small class="font-normal text-slate-600">Jika dinonaktifkan, pengunjung tidak dapat melihat halaman website madrasah.</small></span></label>
                @else
                    @foreach($fields as $key => [$label, $fieldType])
                        @if($fieldType === 'textarea')<x-ui.textarea :label="$label" name="values[{{ $key }}]">{{ old('values.'.$key, $settings[$key] ?? '') }}</x-ui.textarea>@else<x-ui.input :label="$label" name="values[{{ $key }}]" :type="$fieldType" :value="old('values.'.$key, $settings[$key] ?? '')" />@endif
                    @endforeach
                @endif

                @if(in_array($group, ['hero', 'about', 'ppdb', 'seo']))
                    @php($imageKey = $group === 'seo' ? 'og_image' : $group.'_image')
                    <label class="block text-sm font-medium text-emerald-950"><span>Gambar</span><input class="mt-1" type="file" name="{{ $imageKey }}" accept=".jpg,.jpeg,.png,.webp"><span class="mt-1 block text-xs font-normal text-slate-500">JPG, PNG, atau WEBP; maksimum 4 MB.</span></label>
                    @if(!empty($settings[$imageKey]))<div><p class="mb-1 text-sm font-medium text-emerald-950">Gambar saat ini</p><img src="{{ asset('storage/'.$settings[$imageKey]) }}" class="h-36 w-full max-w-sm rounded-xl object-cover" alt="Gambar saat ini"></div>@endif
                @endif
                <div class="flex justify-end border-t border-slate-100 pt-5"><x-ui.button type="submit">Simpan {{ strtolower($groups[$group]['label']) }}</x-ui.button></div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
