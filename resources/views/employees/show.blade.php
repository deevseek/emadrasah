<x-app-layout :title="$title">
@php
    $display = fn ($value) => filled($value) ? $value : '-';
    $birth = collect([$employee->birth_place, $employee->birth_date?->format('d-m-Y')])->filter()->join(' / ');
@endphp
<div class="space-y-6">
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Data Personalia</p>
                <h2 class="mt-1 text-3xl font-bold text-emerald-950">{{ $employee->fullName() }}</h2>
                <p class="mt-2 text-slate-600">{{ $display($employee->position) }} · {{ $display($employee->employee_status?->label()) }} · {{ $display($employee->mainNumber()) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('employees.update')
                    <a class="rounded-xl border border-slate-300 bg-white px-4 py-2 font-semibold text-slate-900 hover:bg-slate-50" href="{{ route('employees.edit', $employee) }}">Edit Data</a>
                @endcan
                @can('employees.activate')
                    <form method="post" action="{{ $employee->is_active ? route('employees.deactivate', $employee) : route('employees.activate', $employee) }}" onsubmit="return confirm('Konfirmasi perubahan status pegawai?')">
                        @csrf
                        @method('patch')
                        <button class="rounded-xl bg-amber-500 px-4 py-2 font-semibold text-white hover:bg-amber-600">{{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan Kembali' }}</button>
                    </form>
                @endcan
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-emerald-950">1. Identitas Utama</h3>
                <p class="mt-1 text-sm text-slate-500">Detail mengikuti isian utama pada form tambah guru dan pegawai.</p>
                <dl class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach([
                        'Nama Lengkap' => $employee->fullName(),
                        'L/P' => $employee->gender?->value === 'laki_laki' ? 'L - Laki-laki' : ($employee->gender?->value === 'perempuan' ? 'P - Perempuan' : null),
                        'Tempat/Tanggal Lahir' => $birth,
                        'Status' => $employee->employee_status?->label(),
                        'Nomor Induk Yayasan (NIY)' => $employee->employee_number,
                        'NIP' => $employee->nip,
                        'Pangkat/Golongan Ruang' => $employee->rank_grade,
                        'Peg.ID' => $employee->peg_id,
                        'Pendidikan Terakhir' => $employee->last_education,
                        'Jabatan' => $employee->position,
                    ] as $label => $value)
                        <div @class(['xl:col-span-2' => $label === 'Nama Lengkap'])>
                            <dt class="text-xs font-bold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $display($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-emerald-950">2. Sertifikasi dan Beban Mengajar</h3>
                <p class="mt-1 text-sm text-slate-500">Menampilkan status sertifikasi, mapel sertifikasi, dan jumlah JPL sesuai form tambah.</p>
                <dl class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach([
                        'Sertifikasi - Impassing' => $employee->certification_status,
                        'Mapel Sertifikasi' => $employee->certification_subject,
                        'Jumlah JPL' => $employee->weekly_teaching_hours,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-bold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $display($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-emerald-950">3. Rekening dan Kontak Aktif</h3>
                <p class="mt-1 text-sm text-slate-500">Data rekening dan kontak aktif sesuai kolom pada form tambah.</p>
                <dl class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach([
                        'Jenis Rekening' => $employee->bank_name,
                        'No. Rekening' => $employee->bank_account_number,
                        'No. HP/WA Aktif' => $employee->whatsapp,
                        'E-mail Aktif' => $employee->email,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-bold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $display($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <section class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="font-bold text-emerald-950">Akun Pengguna</h3>
                <p class="mt-2 text-sm">{{ $employee->user?->email ?? 'Belum terhubung' }}</p>
                @can('employees.link-account')
                    <form class="mt-4 space-y-2" method="post" action="{{ route('employees.link-account', $employee) }}">
                        @csrf
                        <select name="user_id" class="w-full rounded-xl border-slate-300"><option value="">Pilih akun belum terhubung</option>@foreach($availableUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>@endforeach</select>
                        <button class="rounded-xl bg-emerald-800 px-3 py-2 text-sm font-semibold text-white">Hubungkan Akun</button>
                    </form>
                    <form class="mt-4 space-y-2" method="post" action="{{ route('employees.create-account', $employee) }}">
                        @csrf
                        <input name="email" value="{{ old('email', $employee->email) }}" class="w-full rounded-xl border-slate-300" placeholder="Email akun baru">
                        <select name="role" class="w-full rounded-xl border-slate-300">@foreach($roles as $role)<option value="{{ $role->name }}">{{ $role->display_name ?? $role->name }}</option>@endforeach</select>
                        <button class="rounded-xl border px-3 py-2 text-sm font-semibold">Buat Akun</button>
                    </form>
                @endcan
            </div>
        </section>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-emerald-950">Kelola Dokumen</h3>@can('employees.manage-documents')<form class="mt-4 grid gap-3 md:grid-cols-5" method="post" enctype="multipart/form-data" action="{{ route('employees.documents.store', $employee) }}">@csrf<select name="type" class="rounded-xl border-slate-300"><option value="">Jenis dokumen</option>@foreach($documentTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select><input name="document_number" class="rounded-xl border-slate-300" placeholder="Nomor dokumen"><input type="date" name="document_date" class="rounded-xl border-slate-300"><input type="file" name="file" class="rounded-xl border border-slate-300 p-2"><button class="rounded-xl bg-emerald-800 px-3 py-2 font-semibold text-white">Unggah</button></form>@endcan<div class="mt-4 divide-y">@forelse($employee->documents as $document)<div class="flex items-center justify-between py-3"><div><p class="font-semibold">{{ $document->type->label() }}</p><p class="text-sm text-slate-500">{{ $document->document_number ?? '-' }} · {{ $document->file_name }}</p></div><div class="flex gap-2"><a class="text-sm font-semibold text-emerald-700" href="{{ route('employee-documents.download', $document) }}">Download</a>@can('employees.manage-documents')<form method="post" action="{{ route('employee-documents.destroy', $document) }}" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('delete')<button class="text-sm font-semibold text-rose-600">Hapus</button></form>@endcan</div></div>@empty<p class="py-4 text-sm text-slate-600">Belum ada dokumen pegawai.</p>@endforelse</div></section>
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-emerald-950">Aktivitas Perubahan Terakhir</h3><div class="mt-3 divide-y">@forelse($activities as $activity)<p class="py-2 text-sm text-slate-600">{{ $activity->created_at->format('d-m-Y H:i') }} · {{ $activity->event }}</p>@empty<p class="text-sm text-slate-600">Belum ada aktivitas tercatat.</p>@endforelse</div></section>
</div>
</x-app-layout>
