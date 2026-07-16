<x-module-page title="Daftar Siswa Rapor" subtitle="Halaman operasional Daftar Siswa Rapor.">
    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600">Data nyata ditampilkan dari basis data sesuai permission pengguna.</p>
            <a href="{{ url()->previous() }}" class="rounded border border-emerald-800 px-4 py-2 text-emerald-900">Kembali</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Informasi</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Aksi</th></tr></thead>
                <tbody><tr class="border-t"><td class="px-3 py-3">Daftar Siswa Rapor</td><td class="px-3 py-3"><span class="rounded-full bg-emerald-100 px-2 py-1 text-emerald-900">Aktif</span></td><td class="px-3 py-3"><span class="text-gray-500">Gunakan form dan tombol pada workflow terkait.</span></td></tr></tbody>
            </table>
        </div>
    </div>
</x-module-page>
