<x-app-layout :title="$title">
<form class="space-y-6" method="post" action="{{ $mode === 'create' ? route('employees.store') : route('employees.update', $employee) }}">
@csrf
@if($mode === 'edit') @method('put') @endif
<input type="hidden" name="is_active" value="{{ old('is_active', $employee->exists ? (int) $employee->is_active : 1) }}">

<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Data Personalia</p>
            <h2 class="text-xl font-bold text-emerald-950">{{ $mode === 'create' ? 'Tambah' : 'Edit' }} Guru & Pegawai</h2>
            <p class="mt-1 text-sm text-slate-600">Form ini mengikuti kolom file XLS Data Personalia yang diunggah.</p>
        </div>
        @can('employees.create')
            <a href="{{ route('employees.import.form') }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">Upload XLSX</a>
        @endcan
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1400px] border-collapse text-sm">
            <thead class="bg-emerald-100 text-xs uppercase text-emerald-950">
                <tr>
                    <th class="border border-slate-300 px-3 py-2 text-center">NO</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Nama Lengkap</th>
                    <th class="border border-slate-300 px-3 py-2 text-center">L/P</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Tempat, Tgl Lahir</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Status</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Nomor Induk Yayasan (NIY)</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">NIP</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Pangkat/Golongan Ruang</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Peg.ID</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Pendidikan Terakhir</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Jabatan</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Sertifikasi - Impassing</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Mapel Sertifikasi</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Jumlah JPL</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">Jenis Rekening</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">No. Rekening</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">No. HP/WA Aktif</th>
                    <th class="border border-slate-300 px-3 py-2 text-left">E-mail Aktif</th>
                </tr>
            </thead>
            <tbody>
                <tr class="align-top">
                    <td class="border border-slate-300 bg-slate-50 px-3 py-3 text-center font-semibold">1</td>
                    <td class="border border-slate-300 p-2"><input name="name" value="{{ old('name', $employee->name) }}" class="w-56 rounded-lg border-slate-300 text-sm" required>@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><select name="gender" class="w-24 rounded-lg border-slate-300 text-sm" required><option value="">Pilih</option>@foreach($genders as $gender)<option value="{{ $gender->value }}" @selected(old('gender', $employee->gender?->value) === $gender->value)>{{ $gender->value === 'laki_laki' ? 'L' : 'P' }}</option>@endforeach</select>@error('gender')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><div class="space-y-2"><input name="birth_place" value="{{ old('birth_place', $employee->birth_place) }}" class="w-44 rounded-lg border-slate-300 text-sm" placeholder="Tempat"><input type="date" name="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" class="w-44 rounded-lg border-slate-300 text-sm"></div>@error('birth_place')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror @error('birth_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><select name="employee_status" class="w-32 rounded-lg border-slate-300 text-sm" required><option value="">Pilih</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('employee_status', $employee->employee_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select>@error('employee_status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" class="w-40 rounded-lg border-slate-300 text-sm">@error('employee_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="nip" value="{{ old('nip', $employee->nip) }}" class="w-36 rounded-lg border-slate-300 text-sm">@error('nip')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="rank_grade" value="{{ old('rank_grade', $employee->rank_grade) }}" class="w-44 rounded-lg border-slate-300 text-sm">@error('rank_grade')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="peg_id" value="{{ old('peg_id', $employee->peg_id) }}" class="w-36 rounded-lg border-slate-300 text-sm">@error('peg_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="last_education" value="{{ old('last_education', $employee->last_education) }}" class="w-36 rounded-lg border-slate-300 text-sm">@error('last_education')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="position" value="{{ old('position', $employee->position) }}" class="w-40 rounded-lg border-slate-300 text-sm" required>@error('position')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="certification_status" value="{{ old('certification_status', $employee->certification_status) }}" class="w-40 rounded-lg border-slate-300 text-sm">@error('certification_status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="certification_subject" value="{{ old('certification_subject', $employee->certification_subject) }}" class="w-40 rounded-lg border-slate-300 text-sm">@error('certification_subject')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input type="number" min="0" max="80" name="weekly_teaching_hours" value="{{ old('weekly_teaching_hours', $employee->weekly_teaching_hours) }}" class="w-28 rounded-lg border-slate-300 text-sm">@error('weekly_teaching_hours')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="w-32 rounded-lg border-slate-300 text-sm">@error('bank_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" class="w-40 rounded-lg border-slate-300 text-sm">@error('bank_account_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input name="whatsapp" value="{{ old('whatsapp', $employee->whatsapp) }}" class="w-36 rounded-lg border-slate-300 text-sm">@error('whatsapp')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                    <td class="border border-slate-300 p-2"><input type="email" name="email" value="{{ old('email', $employee->email) }}" class="w-56 rounded-lg border-slate-300 text-sm">@error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div class="flex justify-end gap-3">
    <a class="rounded-xl border px-4 py-2 font-semibold" href="{{ $employee->exists ? route('employees.show', $employee) : route('employees.index') }}">Batal</a>
    <button class="rounded-xl bg-emerald-800 px-5 py-2 font-semibold text-white">Simpan Data</button>
</div>
</form>
</x-app-layout>
