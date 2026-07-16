@component('finance._page', [
    'title' => 'Riwayat Pembayaran',
    'description' => 'Pantau seluruh pembayaran siswa, petugas penerima, metode, dan status pembatalan.',
])
    @if (session('status'))
        <x-ui.alert class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-4">
            <x-ui.input
                name="search"
                label="Pencarian"
                :value="request('search')"
                placeholder="Nomor pembayaran, siswa, referensi"
            />

            <div class="space-y-1.5">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    <option value="posted" @selected(request('status') === 'posted')>Posted</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                    <option value="refunded" @selected(request('status') === 'refunded')>Refunded</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="student_id">Siswa</label>
                <select id="student_id" name="student_id">
                    <option value="">Semua siswa</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <x-ui.button>Terapkan</x-ui.button>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="route('finance.student-payments.index')"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>

        @can('student-payments.create')
            <x-ui.button
                type="button"
                :href="route('finance.student-payments.create')"
            >
                Catat Pembayaran
            </x-ui.button>
        @endcan
    </div>

    @if ($payments->isEmpty())
        <x-ui.empty-state
            title="Belum ada pembayaran"
            description="Pembayaran yang diposting akan tampil di sini beserta kuitansi dan audit pembatalannya."
        />
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Petugas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td class="font-semibold text-emerald-900">{{ $payment->payment_number }}</td>
                        <td>{{ $payment->payment_date?->format('d/m/Y') }}</td>
                        <td>{{ $payment->student?->name }}</td>
                        <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                        <td>Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</td>
                        <td>{{ $payment->receiver?->name ?? '-' }}</td>
                        <td>
                            <x-ui.badge :variant="$payment->status === 'posted' ? 'success' : 'muted'">
                                {{ str($payment->status)->title() }}
                            </x-ui.badge>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    class="btn btn-secondary px-3 py-1.5"
                                    href="{{ route('finance.student-payments.show', $payment) }}"
                                >
                                    Detail
                                </a>
                                @can('student-payments.print')
                                    <a
                                        class="btn btn-secondary px-3 py-1.5"
                                        href="{{ route('finance.student-payments.receipt', $payment) }}"
                                        target="_blank"
                                    >
                                        Kuitansi
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.pagination :paginator="$payments" />
    @endif
@endcomponent
