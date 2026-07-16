@component('finance._page', [
    'title' => 'Daftar Payroll Pegawai',
    'description' => 'Lihat hasil payroll dan slip gaji sesuai hak akses pengguna.',
])
    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-3">
            <x-ui.input
                name="search"
                label="Pencarian"
                :value="request('search')"
                placeholder="Pegawai atau periode payroll"
            />

            <div class="space-y-1.5">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach (['calculated', 'reviewed', 'approved', 'paid', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ str($status)->title() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <x-ui.button>Terapkan</x-ui.button>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="route('finance.payrolls.index')"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>

        @can('payroll-periods.view')
            <x-ui.button
                type="button"
                variant="secondary"
                :href="route('finance.payroll-periods.index')"
            >
                Kelola Periode
            </x-ui.button>
        @endcan
    </div>

    @if ($payrolls->isEmpty())
        <x-ui.empty-state
            title="Belum ada payroll"
            description="Payroll akan tampil setelah periode dihitung oleh bendahara."
        />
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Pegawai</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Pendapatan</th>
                    <th>Potongan</th>
                    <th>Netto</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payrolls as $payroll)
                    <tr>
                        <td>{{ $payroll->period?->name }}</td>
                        <td>{{ $payroll->employee?->name }}</td>
                        <td>{{ $payroll->attendance_present }}</td>
                        <td>{{ $payroll->attendance_late }}</td>
                        <td>Rp {{ number_format((float) $payroll->total_earnings, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $payroll->total_deductions, 0, ',', '.') }}</td>
                        <td class="font-bold">Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}</td>
                        <td>
                            <x-ui.badge :variant="in_array($payroll->status, ['paid', 'closed'], true) ? 'success' : 'info'">
                                {{ str($payroll->status)->title() }}
                            </x-ui.badge>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    class="btn btn-secondary px-3 py-1.5"
                                    href="{{ route('finance.payrolls.show', $payroll) }}"
                                >
                                    Detail
                                </a>
                                <a
                                    class="btn btn-secondary px-3 py-1.5"
                                    href="{{ route('finance.payrolls.slip', $payroll) }}"
                                    target="_blank"
                                >
                                    Slip
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.pagination :paginator="$payrolls" />
    @endif
@endcomponent
