<x-app-layout :title="$title">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-semibold text-emerald-700">Guru & Pegawai</p>
            <h1 class="mt-1 text-2xl font-bold text-emerald-950">Import Data Personalia XLSX</h1>
            <p class="mt-2 text-sm text-slate-600">Unduh template resmi, lalu isi data sesuai header tersebut. Kolom yang dibaca adalah NAMA LENGKAP, L/P, TEMPAT, TGL LAHIR, STATUS, NOMOR INDUK YAYASAN (NIY), NIP, PANGKAT/GOLONGAN RUANG, Peg.ID, PENDIDIKAN TERAKHIR, JABATAN, SERTIFIKASI - IMPASSING, MAPEL SERTIFIKASI, JUMLAH JPL, JENIS REKENING, NO. REKENING, NO. HP/ WA AKTIF, dan E-MAIL AKTIF.</p>
            <a href="{{ route('employees.import.template') }}" class="mt-4 inline-flex rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Unduh Template XLSX</a>
        </div>
        <form method="post" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            <label class="block text-sm font-semibold text-slate-700" for="file">Berkas XLSX</label>
            <input id="file" name="file" type="file" accept=".xlsx" required class="mt-2 block w-full rounded-xl border border-slate-300 bg-slate-50 p-3 text-sm">
            @error('file')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            <div class="mt-6 flex flex-wrap gap-3">
                <button class="rounded-xl bg-emerald-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Upload dan Import</button>
                <a href="{{ route('employees.import.template') }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-800">Unduh Template</a>
                <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">Kembali</a>
            </div>
        </form>
    </div>
</x-app-layout>
