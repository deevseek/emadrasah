<x-layouts.app :title="($item ? 'Ubah ' : 'Tambah ').$meta['singular']">
    <x-ui.page-header :title="($item ? 'Ubah ' : 'Tambah ').$meta['singular']" description="Isi informasi yang diperlukan. Kolom bertanda bintang wajib diisi.">
        <x-slot:actions><x-ui.button variant="secondary" :href="route('website.content.index', $type)">Batal dan kembali</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <form method="post" enctype="multipart/form-data" action="{{ $item ? route('website.content.update', [$type, $item->id]) : route('website.content.store', $type) }}" class="mt-6 space-y-5">
        @csrf
        @if($item) @method('PUT') @endif

        <x-ui.card>
            <h2 class="text-lg font-bold text-emerald-950">Informasi utama</h2>
            <p class="mt-1 text-sm text-slate-500">Informasi ini akan dibaca pengunjung website.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                @if(in_array($type, ['program', 'achievement', 'news']))
                    <x-ui.input label="Judul *" name="title" :value="data_get($item, 'title')" required autofocus />
                @else
                    <x-ui.input :label="($type === 'testimonial' ? 'Nama pemberi testimoni' : 'Nama fasilitas').' *'" name="name" :value="data_get($item, 'name')" required autofocus />
                @endif

                @if($type === 'news')
                    <label class="block text-sm font-medium text-emerald-950"><span>Kategori *</span><select name="category" class="mt-1" required><option value="">Pilih kategori</option>@foreach(['Berita', 'Kegiatan', 'Prestasi', 'Pengumuman'] as $category)<option value="{{ $category }}" @selected(old('category', data_get($item, 'category')) === $category)>{{ $category }}</option>@endforeach</select>@error('category')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <div class="md:col-span-2"><x-ui.textarea label="Ringkasan singkat" name="excerpt" maxlength="1000" placeholder="Ringkasan yang tampil pada daftar berita.">{{ data_get($item, 'excerpt') }}</x-ui.textarea></div>
                    <div class="md:col-span-2"><x-ui.textarea label="Isi berita *" name="content" class="min-h-64" required placeholder="Tuliskan isi berita secara lengkap.">{{ data_get($item, 'content') }}</x-ui.textarea></div>
                @elseif($type === 'program')
                    <div class="md:col-span-2"><x-ui.textarea label="Ringkasan" name="summary" maxlength="1000" placeholder="Jelaskan program dalam beberapa kalimat.">{{ data_get($item, 'summary') }}</x-ui.textarea></div>
                    <div class="md:col-span-2"><x-ui.textarea label="Deskripsi lengkap" name="description">{{ data_get($item, 'description') }}</x-ui.textarea></div>
                @elseif($type === 'testimonial')
                    <x-ui.input label="Hubungan dengan madrasah" name="relation" :value="data_get($item, 'relation')" placeholder="Contoh: Orang tua siswa" />
                    <div class="md:col-span-2"><x-ui.textarea label="Isi testimoni *" name="description" required>{{ data_get($item, 'content') }}</x-ui.textarea></div>
                    <label class="block text-sm font-medium text-emerald-950"><span>Penilaian</span><select name="rating" class="mt-1">@foreach(range(5, 1) as $rating)<option value="{{ $rating }}" @selected((int) old('rating', data_get($item, 'rating', 5)) === $rating)>{{ $rating }} bintang</option>@endforeach</select></label>
                @else
                    @if($type === 'achievement')
                        <x-ui.input label="Kategori" name="category" :value="data_get($item, 'category')" placeholder="Contoh: Akademik" />
                        <x-ui.input label="Tahun" name="year" type="number" min="1900" max="2100" :value="data_get($item, 'year')" />
                        <x-ui.input label="Tanggal" name="date" type="date" :value="data_get($item, 'date')?->format('Y-m-d')" />
                    @endif
                    <div class="md:col-span-2"><x-ui.textarea label="Deskripsi" name="description">{{ data_get($item, 'description') }}</x-ui.textarea></div>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-bold text-emerald-950">Gambar dan publikasi</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label class="block text-sm font-medium text-emerald-950"><span>{{ $type === 'testimonial' ? 'Foto' : 'Gambar' }}</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="mt-1"><span class="mt-1 block text-xs font-normal text-slate-500">JPG, PNG, atau WEBP; maksimum 4 MB.</span>@error('image')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                @php($currentImage = data_get($item, $type === 'news' ? 'featured_image' : ($type === 'testimonial' ? 'photo' : 'image')))
                @if($currentImage)<div><p class="mb-1 text-sm font-medium text-emerald-950">Gambar saat ini</p><img src="{{ asset('storage/'.$currentImage) }}" class="h-28 w-44 rounded-xl object-cover" alt="Gambar saat ini"></div>@endif

                @if($type === 'news')
                    <label class="block text-sm font-medium text-emerald-950"><span>Status *</span><select name="status" class="mt-1" required><option value="draft" @selected(old('status', data_get($item, 'status')?->value ?? 'draft') === 'draft')>Simpan sebagai draf</option><option value="published" @selected(old('status', data_get($item, 'status')?->value) === 'published')>Terbitkan</option></select></label>
                    <x-ui.input label="Waktu terbit" name="published_at" type="datetime-local" :value="data_get($item, 'published_at')?->format('Y-m-d\TH:i')" />
                @else
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4"><input class="mt-0.5 h-4 w-4" type="checkbox" name="is_active" value="1" @checked(old('is_active', data_get($item, 'is_active', true)))><span><strong class="block text-emerald-950">Tampilkan di website</strong><small class="font-normal text-slate-500">Hilangkan centang untuk menyembunyikan konten sementara.</small></span></label>
                @endif
                @if(in_array($type, ['program', 'achievement', 'news']))<label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4"><input class="mt-0.5 h-4 w-4" type="checkbox" name="featured" value="1" @checked(old('featured', data_get($item, 'featured', false)))><span><strong class="block text-emerald-950">Jadikan unggulan</strong><small class="font-normal text-slate-500">Konten memperoleh penekanan pada halaman depan.</small></span></label>@endif
            </div>
        </x-ui.card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><x-ui.button variant="secondary" :href="route('website.content.index', $type)">Batal</x-ui.button><x-ui.button type="submit">{{ $item ? 'Simpan perubahan' : 'Simpan '.$meta['singular'] }}</x-ui.button></div>
    </form>
</x-layouts.app>
