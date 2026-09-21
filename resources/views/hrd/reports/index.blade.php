<x-layouts.app :title="$title" :breadcrumbs="['HRD', 'Laporan HRD']">
    <div class="space-y-4">
        <x-ui.card>
            <form class="flex flex-wrap gap-3">
                <input type="date" name="start_date" value="{{ $start->format('Y-m-d') }}">
                <input type="date" name="end_date" value="{{ $end->format('Y-m-d') }}">
                <button class="btn btn-primary">Terapkan</button>
                <a href="{{ route('hrd.reports.print', ['start_date' => $start->format('Y-m-d'), 'end_date' => $end->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-secondary">Cetak</a>
            </form>
        </x-ui.card>

        <div class="grid gap-3 md:grid-cols-4">
            @foreach (['Total Gaji Bersih' => 'Rp '.number_format((float) ($payrollSummary->net ?? 0), 0, ',', '.'), 'Jumlah Pegawai Payroll' => $payrollSummary->employees ?? 0, 'Total Dicairkan' => 'Rp '.number_format((float) ($advanceSummary->disbursed ?? 0), 0, ',', '.'), 'Sisa Kasbon' => 'Rp '.number_format((float) ($advanceSummary->remaining ?? 0), 0, ',', '.')] as $label => $value)
                <x-ui.card><p class="text-sm text-slate-500">{{ $label }}</p><b class="text-xl text-emerald-950">{{ $value }}</b></x-ui.card>
            @endforeach
        </div>

        <x-ui.card>
            <h2 class="font-bold">Ringkasan Absensi</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (\App\Enums\Hrd\AttendanceStatus::cases() as $status)
                    <span class="badge">{{ $status->label() }}: {{ $attendanceSummary[$status->value] ?? 0 }}</span>
                @endforeach
                <span class="badge">Terlambat: {{ $minutes->late ?? 0 }} menit</span>
                <span class="badge">Lembur: {{ $minutes->overtime ?? 0 }} menit</span>
            </div>
        </x-ui.card>

        <x-ui.card class="overflow-x-auto !p-0">
            <table class="w-full text-sm">
                <thead><tr><th>Personalia</th><th>Tanggal</th><th>Status</th><th>Jam Kerja</th><th>Terlambat</th><th>Lembur</th></tr></thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr class="border-t">
                            <td class="p-3">{{ $attendance->personnel->full_name }}</td>
                            <td>{{ $attendance->attendance_date->format('d/m/Y') }}</td>
                            <td>{{ $attendance->status->label() }}</td>
                            <td>{{ $attendance->check_in_time && $attendance->check_out_time ? $attendance->check_in_time->diffForHumans($attendance->check_out_time, true) : '—' }}</td>
                            <td>{{ $attendance->late_minutes }}</td>
                            <td>{{ $attendance->overtime_minutes }}</td>
                        </tr>
                    @empty
                        <tr class="border-t"><td colspan="6" class="p-3 text-center text-slate-500">Tidak ada data absensi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if ($attendances->hasPages())
                <div class="border-t p-4">
                    <p class="mb-3 text-sm text-slate-500">Menampilkan {{ $attendances->firstItem() }}–{{ $attendances->lastItem() }} dari {{ $attendances->total() }} data absensi.</p>
                    {{ $attendances->onEachSide(1)->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
