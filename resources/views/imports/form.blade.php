<x-app-layout><x-slot name="header"><h1 class="text-xl font-semibold text-emerald-950">{{ $title }}</h1><p class="text-sm text-slate-600">Unggah XLSX, periksa preview, lalu proses data yang valid.</p></x-slot>
<div class="space-y-6">
@if (session('status'))
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800" role="status">{{ session('status') }}</div>
@endif
<div class="rounded-xl border border-emerald-100 bg-white p-6 shadow"><div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-semibold text-emerald-950">Berkas sumber</h2><p class="text-sm text-slate-500">Maksimal 10 MB. Master guru, kelas, dan mata pelajaran tidak dibuat otomatis.</p></div><a class="rounded border border-emerald-800 px-4 py-2 text-emerald-900" href="{{ route($kind==='teaching'?'teaching-assignments.import.template':'schedules.import.template') }}">Unduh Template XLSX</a></div>
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <p class="font-semibold">Data belum dapat diproses.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form method="post" enctype="multipart/form-data" action="{{ route($kind==='teaching'?'teaching-assignments.import.preview':'schedules.import.preview') }}" class="grid gap-4 md:grid-cols-2">@csrf
<label>Tahun Ajaran<select required name="academic_year_id" class="mt-1 w-full"><option value="">Pilih</option>@foreach($academicYears as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select></label><label>Semester<select required name="semester_id" class="mt-1 w-full"><option value="">Pilih</option>@foreach($academicYears->flatMap->semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach</select></label>
@if($kind==='teaching')<label>Mode impor<select name="mode" class="mt-1 w-full"><option value="create">Tambah data baru saja</option><option value="update">Tambah dan perbarui data yang cocok</option><option value="replace">Tambah/perbarui dan nonaktifkan data lama</option></select><span class="text-xs text-amber-700">Mode terakhir memerlukan konfirmasi tambahan.</span></label>@endif
<label>File XLSX<input required type="file" name="file" accept=".xlsx" class="mt-1 block w-full rounded border p-2"></label><div class="md:col-span-2"><button class="rounded bg-emerald-900 px-5 py-2 text-white">Preview</button></div></form></div>
<div class="rounded-xl bg-white p-6 shadow"><h2 class="mb-3 font-semibold text-emerald-950">Riwayat Impor</h2><div class="overflow-auto"><table class="min-w-full text-sm"><thead><tr><th>File</th><th>Tanggal</th><th>Pengguna</th><th>Berhasil</th><th>Dilewati</th><th>Gagal</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>@forelse($batches as $batch)<tr class="border-t"><td>{{ $batch->original_filename }}</td><td>{{ $batch->created_at?->format('d/m/Y H:i') }}</td><td>{{ $batch->importer?->name ?? '-' }}</td><td>{{ $batch->imported_rows }}</td><td>{{ $batch->skipped_rows }}</td><td>{{ $batch->error_rows }}</td><td>{{ $batch->status }}</td><td>@if($batch->status==='completed')<form method="post" action="{{ route('import-batches.rollback',$batch) }}" onsubmit="return confirm('Rollback hanya data batch ini?')">@csrf<button class="text-red-700">Rollback</button></form>@endif</td></tr>@empty<tr><td colspan="8" class="py-6 text-center text-slate-500">Belum ada riwayat.</td></tr>@endforelse</tbody></table></div></div></div></x-app-layout>
