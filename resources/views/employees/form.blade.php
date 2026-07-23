<x-app-layout :title="$title">
<form class="space-y-6" method="post" action="{{ $mode === 'create' ? route('employees.store') : route('employees.update', $employee) }}">
@csrf
@if($mode === 'edit') @method('put') @endif
<input type="hidden" name="is_active" value="{{ old('is_active', $employee->exists ? (int) $employee->is_active : 1) }}">

<div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Data Personalia</p>
            <h2 class="mt-1 text-2xl font-bold text-emerald-950">{{ $mode === 'create' ? 'Tambah' : 'Edit' }} Guru & Pegawai</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Isi data sesuai kolom XLS Data Personalia. Untuk input banyak data sekaligus, gunakan tombol Upload XLSX.</p>
        </div>
        @can('employees.create')
            <a href="{{ route('employees.import.form') }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Upload XLSX</a>
        @endcan
    </div>
</div>

<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h3 class="text-lg font-bold text-emerald-950">1. Identitas Utama</h3>
    <p class="mt-1 text-sm text-slate-500">Sesuai kolom Nama Lengkap, L/P, Tempat Tgl Lahir, Status, NIY, NIP, Pangkat/Golongan Ruang, Peg.ID, Pendidikan Terakhir, dan Jabatan.</p>
    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <label class="block xl:col-span-2"><span class="text-sm font-semibold text-slate-700">Nama Lengkap</span><input name="name" value="{{ old('name', $employee->name) }}" class="mt-1 w-full rounded-xl border-slate-300" required>@error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">L/P</span><select name="gender" class="mt-1 w-full rounded-xl border-slate-300" required><option value="">Pilih</option>@foreach($genders as $gender)<option value="{{ $gender->value }}" @selected(old('gender', $employee->gender?->value) === $gender->value)>{{ $gender->value === 'laki_laki' ? 'L - Laki-laki' : 'P - Perempuan' }}</option>@endforeach</select>@error('gender')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Tempat Lahir</span><input name="birth_place" value="{{ old('birth_place', $employee->birth_place) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('birth_place')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Tanggal Lahir</span><input type="date" name="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('birth_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Status</span><select name="employee_status" class="mt-1 w-full rounded-xl border-slate-300" required><option value="">Pilih</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('employee_status', $employee->employee_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select>@error('employee_status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Nomor Induk Yayasan (NIY)</span><input name="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('employee_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">NIP</span><input name="nip" value="{{ old('nip', $employee->nip) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('nip')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Pangkat/Golongan Ruang</span><input name="rank_grade" value="{{ old('rank_grade', $employee->rank_grade) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('rank_grade')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Peg.ID</span><input name="peg_id" value="{{ old('peg_id', $employee->peg_id) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('peg_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Pendidikan Terakhir</span><input name="last_education" value="{{ old('last_education', $employee->last_education) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('last_education')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Jabatan</span><input name="position" value="{{ old('position', $employee->position) }}" class="mt-1 w-full rounded-xl border-slate-300" required>@error('position')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
    </div>
</section>

<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h3 class="text-lg font-bold text-emerald-950">2. Sertifikasi dan Beban Mengajar</h3>
    <p class="mt-1 text-sm text-slate-500">Sesuai kolom Sertifikasi-Impassing, Mapel Sertifikasi, dan Jumlah JPL.</p>
    <div class="mt-5 grid gap-4 md:grid-cols-3">
        <label class="block"><span class="text-sm font-semibold text-slate-700">Sertifikasi - Impassing</span><input name="certification_status" value="{{ old('certification_status', $employee->certification_status) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('certification_status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Mapel Sertifikasi</span><input name="certification_subject" value="{{ old('certification_subject', $employee->certification_subject) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('certification_subject')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Jumlah JPL</span><input type="number" min="0" max="80" name="weekly_teaching_hours" value="{{ old('weekly_teaching_hours', $employee->weekly_teaching_hours) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('weekly_teaching_hours')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
    </div>
</section>

<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h3 class="text-lg font-bold text-emerald-950">3. Rekening dan Kontak Aktif</h3>
    <p class="mt-1 text-sm text-slate-500">Sesuai kolom Jenis Rekening, No. Rekening, No. HP/WA Aktif, dan E-mail Aktif.</p>
    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <label class="block"><span class="text-sm font-semibold text-slate-700">Jenis Rekening</span><input name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('bank_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">No. Rekening</span><input name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('bank_account_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">No. HP/WA Aktif</span><input name="whatsapp" value="{{ old('whatsapp', $employee->whatsapp) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('whatsapp')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">E-mail Aktif</span><input type="email" name="email" value="{{ old('email', $employee->email) }}" class="mt-1 w-full rounded-xl border-slate-300">@error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</label>
    </div>
</section>

<div class="sticky bottom-0 z-10 -mx-2 flex justify-end gap-3 border-t border-slate-200 bg-slate-100/90 px-2 py-4 backdrop-blur">
    <a class="rounded-xl border border-slate-300 bg-white px-4 py-2 font-semibold text-slate-800" href="{{ $employee->exists ? route('employees.show', $employee) : route('employees.index') }}">Batal</a>
    <button class="rounded-xl bg-emerald-800 px-5 py-2 font-semibold text-white hover:bg-emerald-700">Simpan Data</button>
</div>
</form>
</x-app-layout>
