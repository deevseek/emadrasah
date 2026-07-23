<x-app-layout :title="$title">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-semibold text-emerald-700">Guru & Pegawai</p>
            <h1 class="mt-1 text-2xl font-bold text-emerald-950">Import Data Personalia XLSX</h1>
            <p class="mt-2 text-sm text-slate-600">Unggah berkas Excel seperti screenshot: header tabel berada di baris 10 dan data mulai baris 11 dengan kolom NO sampai E-MAIL AKTIF.</p>
        </div>
        <form method="post" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            <label class="block text-sm font-semibold text-slate-700" for="file">Berkas XLSX</label>
            <input id="file" name="file" type="file" accept=".xlsx" required class="mt-2 block w-full rounded-xl border border-slate-300 bg-slate-50 p-3 text-sm">
            @error('file')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            <div class="mt-6 flex flex-wrap gap-3">
                <button class="rounded-xl bg-emerald-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Upload dan Import</button>
                <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">Kembali</a>
            </div>
        </form>
    </div>
</x-app-layout>
