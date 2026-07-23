<x-app-layout title="Data Siswa">
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-black text-emerald-950">Data Siswa</h2>
            <p class="text-sm text-slate-500">Kelola identitas siswa, wali, dokumen, dan riwayat status. Daftar awal menampilkan siswa aktif.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('students.export')<a href="{{ route('students.export', request()->query()) }}" class="btn btn-secondary">Export XLSX</a>@endcan
            @can('students.create')<a href="{{ route('students.import.form') }}" class="btn btn-secondary">Upload XLSX</a><a href="{{ route('students.create') }}" class="btn btn-primary">Tambah Siswa</a>@endcan
        </div>
    </div>

    <form class="card card-body grid gap-3 md:grid-cols-6">
        <input name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, NISN, NIK, KK, wali" class="md:col-span-2">
        <select name="status"><option value="">Siswa Aktif</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ $status->label() }}</option>@endforeach</select>
        <select name="gender"><option value="">Semua jenis kelamin</option>@foreach($genders as $gender)<option value="{{ $gender->value }}" @selected(request('gender')===$gender->value)>{{ $gender->label() }}</option>@endforeach</select>
        <input name="year_in" value="{{ request('year_in') }}" placeholder="Tahun masuk">
        <div class="flex gap-2"><button class="btn btn-primary">Filter</button><a class="btn btn-secondary" href="{{ route('students.index') }}">Reset</a></div>
    </form>

    @if(session('import_errors'))<div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900"><p class="font-semibold">Catatan import:</p><ul class="mt-1 list-disc pl-5">@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($errors->has('student_ids'))<div class="rounded-2xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first('student_ids') }}</div>@endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Jumlah hasil: {{ number_format($totalStudents) }}</p>
        @can('students.delete')
            @if($students->isNotEmpty())
                <form id="bulk-delete-students" method="POST" action="{{ route('students.bulk-destroy') }}" onsubmit="return confirm('Yakin ingin menghapus semua siswa yang dipilih? Data akan dinonaktifkan.');">
                    @csrf
                    @method('DELETE')
                </form>
                <button form="bulk-delete-students" class="btn btn-secondary border-red-200 bg-red-50 text-red-700 hover:bg-red-100">Hapus Siswa Terpilih</button>
            @endif
        @endcan
    </div>

    @if($students->isEmpty())
        <div class="empty-state">Belum ada data siswa sesuai filter.</div>
    @else
        <div class="grid gap-3 md:hidden">
            @foreach($students as $student)
                @php($primary = $student->guardians->first(fn($g)=>(bool)$g->pivot->is_primary) ?? $student->guardians->first())
                <div class="card card-body">
                    <div class="flex gap-3">
                        @can('students.delete')<input form="bulk-delete-students" type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="mt-1 h-4 w-4 rounded border-slate-300">@endcan
                        <div>@if($student->photo_path)<img src="{{ Storage::url($student->photo_path) }}" class="h-12 w-12 rounded-full object-cover" alt="Foto {{ $student->name }}">@else<div class="grid h-12 w-12 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-800">{{ str($student->name)->substr(0,2)->upper() }}</div>@endif</div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-emerald-950">{{ $student->name }}</p>
                            <p class="text-xs text-slate-600">NISN {{ $student->national_student_number ?? '-' }} · {{ $student->student_status->label() }}</p>
                            <p class="text-xs text-slate-600">Rombel: {{ $student->activeEnrollment?->classroom?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-600">Kontak utama: {{ $primary?->name ?? '-' }} {{ $primary?->whatsapp ? '('.$primary->whatsapp.')' : '' }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <a class="text-sm font-semibold text-emerald-700" href="{{ route('students.show',$student) }}">Detail</a>
                                @can('students.update')<a class="text-sm font-semibold text-emerald-700" href="{{ route('students.edit',$student) }}">Edit</a>@endcan
                                @can('students.delete')<form method="POST" action="{{ route('students.destroy',$student) }}" onsubmit="return confirm('Yakin ingin menghapus data siswa ini? Data akan dinonaktifkan.');">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-700">Hapus</button></form>@endcan
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="hidden md:block table-wrap">
            <table class="data-table">
                <thead><tr>@can('students.delete')<th>Pilih</th>@endcan<th>Foto</th><th>Nama Lengkap</th><th>NISN</th><th>NIK</th><th>Tempat/Tanggal Lahir</th><th>Rombel</th><th>Jenis Kelamin</th><th>No Telepon</th><th>Kebutuhan Khusus</th><th>Disabilitas</th><th>Nomor KIP/PIP</th><th>Ayah/Ibu/Wali</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($students as $student)
                        <tr>
                            @can('students.delete')<td><input form="bulk-delete-students" type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="h-4 w-4 rounded border-slate-300"></td>@endcan
                            <td>@if($student->photo_path)<img src="{{ Storage::url($student->photo_path) }}" class="h-10 w-10 rounded-full object-cover" alt="Foto {{ $student->name }}">@else<div class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-800">{{ str($student->name)->substr(0,2)->upper() }}</div>@endif</td>
                            <td class="font-semibold">{{ $student->name }}</td><td>{{ $student->national_student_number ?? '-' }}</td><td>{{ $student->national_identity_number ?? '-' }}</td><td>{{ trim(($student->birth_place ?? '').' / '.($student->birth_date?->format('Y-m-d') ?? ''), ' /') ?: '-' }}</td><td>{{ $student->activeEnrollment?->classroom?->name ?? '-' }}</td><td>{{ $student->gender?->label() }}</td><td>{{ $student->phone ?? '-' }}</td><td>{{ $student->special_needs ?: 'Tidak Ada' }}</td><td>{{ $student->disability ?: 'Tidak Ada' }}</td><td>{{ $student->kip_pip_number ?? '-' }}</td><td>{{ $student->guardians->pluck('name')->filter()->join(' / ') ?: '-' }}</td><td><span class="badge badge-success">{{ $student->student_status->label() }}</span></td>
                            <td><div class="flex flex-wrap gap-2"><a class="btn btn-secondary px-3 py-1.5" href="{{ route('students.show',$student) }}">Detail</a>@can('students.update')<a class="btn btn-secondary px-3 py-1.5" href="{{ route('students.edit',$student) }}">Edit</a>@endcan @can('students.delete')<form method="POST" action="{{ route('students.destroy',$student) }}" onsubmit="return confirm('Yakin ingin menghapus data siswa ini? Data akan dinonaktifkan.');">@csrf @method('DELETE')<button class="btn btn-secondary border-red-200 bg-red-50 px-3 py-1.5 text-red-700 hover:bg-red-100">Hapus</button></form>@endcan</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    @endif
</div>
</x-app-layout>
