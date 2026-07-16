@component('finance._page', [
    'title' => 'Detail Payroll Pegawai',
    'description' => ($payroll->employee?->name ?? '-').' — '.($payroll->period?->name ?? '-'),
])
    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <div class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Hadir' => $payroll->attendance_present,
                    'Terlambat' => $payroll->attendance_late,
                    'Izin' => $payroll->attendance_permission,
                    'Sakit' => $payroll->attendance_sick,
                    'Alpha' => $payroll->attendance_alpha,
                    'Direview Oleh' => $payroll->reviewer?->name ?? '-',
                    'Disetujui Oleh' => $payroll->approver?->name ?? '-',
                    'Tanggal Bayar' => $payroll->paid_at?->format('d/m/Y H:i') ?? '-',
                ] as $label => $value)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-1 font-semibold text-slate-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div>
                <h3 class="mb-3 text-base font-bold text-emerald-950">Rincian Komponen</h3>
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Komponen</th>
                            <th>Jenis</th>
                            <th>Kuantitas</th>
                            <th>Tarif</th>
                            <th>Nilai</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payroll->items as $item)
                            <tr>
                                <td>{{ $item->component_name_snapshot }}</td>
                                <td>{{ $item->component_type === 'earning' ? 'Pendapatan' : 'Potongan' }}</td>
                                <td>{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                                <td>
                                    @if ($item->notes === 'Persentase dari gaji pokok')
                                        {{ number_format((float) $item->rate, 2, ',', '.') }} %
                                    @else
                                        Rp {{ number_format((float) $item->rate, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td>Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</td>
                                <td>{{ $item->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
                <span class="text-sm font-semibold text-slate-600">Status</span>
                <x-ui.badge :variant="in_array($payroll->status, ['paid', 'closed'], true) ? 'success' : 'info'">
                    {{ str($payroll->status)->title() }}
                </x-ui.badge>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Gaji Pokok</dt>
                        <dd class="font-semibold">Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Pendapatan</dt>
                        <dd class="font-semibold text-emerald-700">Rp {{ number_format((float) $payroll->total_earnings, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Potongan</dt>
                        <dd class="font-semibold text-rose-700">Rp {{ number_format((float) $payroll->total_deductions, 0, ',', '.') }}</dd>
                    </div>
                    <div class="border-t border-slate-200 pt-3">
                        <div class="flex justify-between gap-3">
                            <dt class="font-semibold text-slate-700">Gaji Bersih</dt>
                            <dd class="font-black text-emerald-950">Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}</dd>
                        </div>
                    </div>
                </dl>
            </div>

            <a
                class="btn btn-secondary w-full"
                href="{{ route('finance.payrolls.slip', $payroll) }}"
                target="_blank"
            >
                Cetak Slip Gaji
            </a>

            @if ($payroll->financialTransaction)
                <a
                    class="btn btn-secondary w-full"
                    href="{{ route('finance.transactions.show', $payroll->financialTransaction) }}"
                >
                    Lihat Jurnal Pembayaran
                </a>
            @endif

            @can('payroll-periods.view')
                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="route('finance.payroll-periods.show', $payroll->period)"
                    class="w-full"
                >
                    Kembali ke Periode
                </x-ui.button>
            @else
                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="route('finance.payrolls.index')"
                    class="w-full"
                >
                    Kembali
                </x-ui.button>
            @endcan
        </aside>
    </div>
@endcomponent
