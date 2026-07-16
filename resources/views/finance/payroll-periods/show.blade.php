@php
    $statusVariant = match ($payrollPeriod->status) {
        'paid', 'closed' => 'success',
        'approved', 'reviewed' => 'info',
        'calculated' => 'warning',
        'cancelled' => 'danger',
        default => 'muted',
    };
@endphp

@component('finance._page', [
    'title' => 'Detail Periode Payroll',
    'description' => $payrollPeriod->name,
])
    @if (session('status'))
        <x-ui.alert class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-5">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <div class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Bulan/Tahun' => str_pad((string) $payrollPeriod->month, 2, '0', STR_PAD_LEFT).'/'.$payrollPeriod->year,
                    'Rentang' => $payrollPeriod->starts_on?->format('d/m/Y').' - '.$payrollPeriod->ends_on?->format('d/m/Y'),
                    'Tanggal Bayar' => $payrollPeriod->payment_date?->format('d/m/Y') ?? '-',
                    'Dibuat Oleh' => $payrollPeriod->creator?->name ?? '-',
                ] as $label => $value)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-1 font-semibold text-slate-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.stat-card
                    label="Jumlah Pegawai"
                    :value="$payrollPeriod->payrolls->count()"
                />
                <x-ui.stat-card
                    label="Total Pendapatan"
                    :value="'Rp '.number_format((float) $payrollPeriod->payrolls->sum('total_earnings'), 0, ',', '.')"
                />
                <x-ui.stat-card
                    label="Total Netto"
                    :value="'Rp '.number_format((float) $payrollPeriod->payrolls->sum('net_salary'), 0, ',', '.')"
                />
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-emerald-950">Payroll Pegawai</h3>
                        <p class="text-sm text-slate-500">Klik detail untuk meninjau komponen, absensi, dan jurnal pembayaran.</p>
                    </div>
                </div>

                @if ($payrollPeriod->payrolls->isEmpty())
                    <x-ui.empty-state
                        title="Payroll belum dihitung"
                        description="Jalankan perhitungan dari panel workflow untuk menghasilkan payroll pegawai."
                    />
                @else
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>Pegawai</th>
                                <th>Pendapatan</th>
                                <th>Potongan</th>
                                <th>Netto</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payrollPeriod->payrolls as $payroll)
                                <tr>
                                    <td>{{ $payroll->employee?->name }}</td>
                                    <td>Rp {{ number_format((float) $payroll->total_earnings, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $payroll->total_deductions, 0, ',', '.') }}</td>
                                    <td class="font-bold">Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}</td>
                                    <td>
                                        <x-ui.badge :variant="in_array($payroll->status, ['paid', 'closed'], true) ? 'success' : 'info'">
                                            {{ str($payroll->status)->title() }}
                                        </x-ui.badge>
                                    </td>
                                    <td>
                                        <a
                                            class="btn btn-secondary px-3 py-1.5"
                                            href="{{ route('finance.payrolls.show', $payroll) }}"
                                        >
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </div>
        </div>

        <aside class="space-y-4">
            <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
                <span class="text-sm font-semibold text-slate-600">Status Periode</span>
                <x-ui.badge :variant="$statusVariant">
                    {{ str($payrollPeriod->status)->title() }}
                </x-ui.badge>
            </div>

            <x-ui.button
                type="button"
                variant="secondary"
                :href="route('finance.payroll-periods.index')"
                class="w-full"
            >
                Kembali
            </x-ui.button>

            @can('payroll-periods.manage')
                @if ($payrollPeriod->status === 'draft')
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        :href="route('finance.payroll-periods.edit', $payrollPeriod)"
                        class="w-full"
                    >
                        Edit Periode
                    </x-ui.button>
                @endif
            @endcan

            @can('payrolls.calculate')
                @if (in_array($payrollPeriod->status, ['draft', 'calculated'], true))
                    <form
                        method="POST"
                        action="{{ route('finance.payroll-periods.calculate', $payrollPeriod) }}"
                        onsubmit="return confirm('Hitung ulang payroll periode ini?')"
                    >
                        @csrf
                        <x-ui.button class="w-full">
                            {{ $payrollPeriod->status === 'calculated' ? 'Hitung Ulang Payroll' : 'Hitung Payroll' }}
                        </x-ui.button>
                    </form>
                @endif
            @endcan

            @can('payrolls.review')
                @if ($payrollPeriod->status === 'calculated')
                    <form method="POST" action="{{ route('finance.payroll-periods.review', $payrollPeriod) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button class="w-full">Tandai Sudah Direview</x-ui.button>
                    </form>
                @endif
            @endcan

            @can('payrolls.approve')
                @if ($payrollPeriod->status === 'reviewed')
                    <form
                        method="POST"
                        action="{{ route('finance.payroll-periods.approve', $payrollPeriod) }}"
                        onsubmit="return confirm('Setujui payroll periode ini?')"
                    >
                        @csrf
                        @method('PATCH')
                        <x-ui.button class="w-full">Setujui Payroll</x-ui.button>
                    </form>
                @endif
            @endcan

            @can('payrolls.mark-paid')
                @if ($payrollPeriod->status === 'approved')
                    <form
                        method="POST"
                        action="{{ route('finance.payroll-periods.pay', $payrollPeriod) }}"
                        class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                        onsubmit="return confirm('Posting pembayaran seluruh payroll dan kurangi saldo kas?')"
                    >
                        @csrf
                        @method('PATCH')
                        <div class="space-y-1.5">
                            <label for="cash_account_id">Rekening Pembayaran</label>
                            <select id="cash_account_id" name="cash_account_id" required>
                                <option value="">Pilih rekening kas</option>
                                @foreach ($cashAccounts as $cashAccount)
                                    <option value="{{ $cashAccount->id }}">
                                        {{ $cashAccount->name }} — Rp {{ number_format((float) $cashAccount->current_balance, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <x-ui.button class="w-full">Bayar Seluruh Payroll</x-ui.button>
                    </form>
                @endif
            @endcan

            @can('payrolls.close')
                @if ($payrollPeriod->status === 'paid')
                    <form
                        method="POST"
                        action="{{ route('finance.payroll-periods.close', $payrollPeriod) }}"
                        onsubmit="return confirm('Tutup periode payroll ini?')"
                    >
                        @csrf
                        @method('PATCH')
                        <x-ui.button class="w-full">Tutup Payroll</x-ui.button>
                    </form>
                @endif
            @endcan

            @can('payrolls.reopen')
                @if ($payrollPeriod->status === 'closed')
                    <form
                        method="POST"
                        action="{{ route('finance.payroll-periods.reopen', $payrollPeriod) }}"
                        class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4"
                    >
                        @csrf
                        @method('PATCH')
                        <x-ui.textarea
                            name="reason"
                            label="Alasan Buka Kembali"
                            rows="3"
                            required
                        />
                        <x-ui.button variant="secondary" class="w-full">Buka Kembali ke Paid</x-ui.button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
@endcomponent
