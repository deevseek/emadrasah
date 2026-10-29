<x-layouts.public title="Pendaftaran Siswa Baru">
    <main class="wrap py-12">
        <section class="card p-7">
            <h1 class="text-3xl font-bold text-emerald-950">Pendaftaran Siswa Baru Online</h1>
            <p role="status" class="mt-4 text-slate-700">{{ $statusLabel }}</p>
            <p class="mt-2 text-slate-600">Silakan lihat informasi penerimaan atau hubungi panitia untuk jadwal pendaftaran.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a class="btn btn-primary" href="{{ route('pmbm.public.index') }}">Informasi dan Persyaratan</a>
                <a class="btn btn-secondary" href="{{ route('pmbm.status.form') }}">Cek Status Pendaftaran</a>
            </div>
        </section>
    </main>
</x-layouts.public>
