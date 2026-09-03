<x-layouts.app :title="$title" :breadcrumbs="['HRD', 'Payroll Berdasarkan Absensi']">
    <div class="space-y-5">
        <x-ui.page-header title="Payroll Berdasarkan Absensi" description="Hitung gaji dari rekap absensi aktual pada periode yang dipilih." />

        @can('personnel-payroll.create')
            <x-ui.card>
                <div class="mb-4 flex items-start gap-3 rounded-xl {{ $attendancePayrollEnabled ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900' }} p-4">
                    <x-ui.icon :name="$attendancePayrollEnabled ? 'check-circle' : 'alert-triangle'" class="mt-0.5 h-5 w-5" />
                    <div>
                        <p class="font-semibold">{{ $attendancePayrollEnabled ? 'Perhitungan absensi aktif' : 'Perhitungan absensi tidak aktif' }}</p>
                        <p class="text-sm">{{ $attendancePayrollEnabled ? 'Gaji pokok dihitung dari tarif harian dikali jumlah hari berbayar (hadir, terlambat, sakit, cuti, dan dinas luar).' : 'Gaji pokok masih menggunakan nominal bulanan. Aktifkan “Payroll berdasarkan absensi” pada Pengaturan HRD.' }}</p>
                    </div>
                </div>
                <form method="post" action="{{ route('hrd.payroll.store') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <label class="xl:col-span-2"><span class="label">Personalia</span><select class="input" name="personnel_id" required><option value="">Pilih personalia</option>@foreach($personnel as $person)<option value="{{ $person->id }}" @selected(old('personnel_id') == $person->id)>{{ $person->full_name }} · Rp {{ number_format((float) $person->base_salary, 0, ',', '.') }}</option>@endforeach</select>@error('personnel_id')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                    <label><span class="label">Awal periode</span><input class="input" type="date" name="period_start" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required>@error('period_start')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                    <label><span class="label">Akhir periode</span><input class="input" type="date" name="period_end" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" required>@error('period_end')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                    <label><span class="label">Tanggal pembayaran</span><input class="input" type="date" name="pay_date" value="{{ old('pay_date') }}"></label>
                    <label><span class="label">Tunjangan</span><input class="input" type="number" name="allowance" min="0" step="0.01" value="{{ old('allowance', 0) }}"></label>
                    <label><span class="label">Potongan lain</span><input class="input" type="number" name="deduction" min="0" step="0.01" value="{{ old('deduction', 0) }}"></label>
                    <label><span class="label">Catatan</span><input class="input" name="note" maxlength="2000" value="{{ old('note') }}" placeholder="Opsional"></label>
                    <div class="xl:col-span-4"><button class="btn btn-primary" type="submit">Proses dari Absensi</button></div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card class="overflow-x-auto !p-0">
            <table class="w-full min-w-[980px] text-sm"><thead><tr>@foreach(['Periode', 'Pegawai', 'Metode', 'Hari Berbayar', 'Gaji Pokok', 'Potongan', 'Kasbon', 'Total Bersih', 'Aksi'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
                <tbody>@forelse($payrolls as $payroll)<tr class="border-t"><td class="p-3">{{ $payroll->period_start->format('d/m/Y') }}–{{ $payroll->period_end->format('d/m/Y') }}</td><td>{{ $payroll->personnel->full_name }}</td><td><span class="badge {{ $payroll->calculation_method === 'attendance' ? 'badge-success' : '' }}">{{ $payroll->calculation_method === 'attendance' ? 'Absensi' : 'Bulanan' }}</span></td><td>{{ $payroll->attendance_days }} hari</td><td>Rp {{ number_format((float) $payroll->base_salary, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $payroll->deduction + (float) $payroll->late_deduction, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $payroll->cash_advance_deduction, 0, ',', '.') }}</td><td class="font-bold">Rp {{ number_format((float) $payroll->total, 0, ',', '.') }}</td><td><a class="font-semibold text-emerald-800" href="{{ route('hrd.payroll.show', $payroll) }}">Lihat slip</a></td></tr>@empty<tr><td colspan="9" class="p-8 text-center text-slate-500">Belum ada payroll yang diproses.</td></tr>@endforelse</tbody>
            </table><div class="p-4">{{ $payrolls->links() }}</div>
        </x-ui.card>
    </div>
</x-layouts.app>
