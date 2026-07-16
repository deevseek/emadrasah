@component('finance._page', [
    'title' => 'Detail Pembayaran',
    'description' => $payment->payment_number,
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
                    'Siswa' => $payment->student?->name,
                    'Tanggal' => $payment->payment_date?->format('d/m/Y'),
                    'Metode' => str($payment->payment_method)->replace('_', ' ')->title(),
                    'Petugas' => $payment->receiver?->name ?? '-',
                ] as $label => $value)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-1 font-semibold text-slate-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div>
                <h3 class="mb-3 text-base font-bold text-emerald-950">Alokasi Pembayaran</h3>
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Nomor Tagihan</th>
                            <th>Jenis Tagihan</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payment->allocations as $allocation)
                            <tr>
                                <td>
                                    <a
                                        class="font-semibold text-emerald-700 hover:underline"
                                        href="{{ route('finance.student-invoices.show', $allocation->invoice) }}"
                                    >
                                        {{ $allocation->invoice?->invoice_number }}
                                    </a>
                                </td>
                                <td>{{ $allocation->invoice?->feeType?->name }}</td>
                                <td>Rp {{ number_format((float) $allocation->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold">
                            <td colspan="2" class="text-right">Total</td>
                            <td>Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </x-ui.table>
            </div>

            @if ($payment->notes)
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $payment->notes }}</p>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
                <span class="text-sm font-semibold text-slate-600">Status</span>
                <x-ui.badge :variant="$payment->status === 'posted' ? 'success' : 'muted'">
                    {{ str($payment->status)->title() }}
                </x-ui.badge>
            </div>

            <div class="rounded-xl border border-slate-200 p-4 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor Referensi</p>
                <p class="mt-1 font-semibold text-slate-800">{{ $payment->reference_number ?? '-' }}</p>
            </div>

            @if ($payment->financialTransaction)
                <a
                    class="btn btn-secondary w-full"
                    href="{{ route('finance.transactions.show', $payment->financialTransaction) }}"
                >
                    Lihat Jurnal Pembayaran
                </a>
            @endif

            @can('student-payments.print')
                <a
                    class="btn btn-secondary w-full"
                    href="{{ route('finance.student-payments.receipt', $payment) }}"
                    target="_blank"
                >
                    Cetak Kuitansi
                </a>
            @endcan

            <x-ui.button
                type="button"
                variant="secondary"
                :href="route('finance.student-payments.index')"
                class="w-full"
            >
                Kembali
            </x-ui.button>

            @if ($payment->status === 'cancelled')
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p class="font-semibold">Pembayaran Dibatalkan</p>
                    <p class="mt-1 whitespace-pre-line">{{ $payment->cancellation_reason }}</p>
                    <p class="mt-2 text-xs">
                        {{ $payment->canceller?->name ?? '-' }} · {{ $payment->cancelled_at?->format('d/m/Y H:i') }}
                    </p>
                    @if ($payment->financialTransaction?->reversalTransaction)
                        <a
                            class="mt-2 inline-block font-semibold underline"
                            href="{{ route('finance.transactions.show', $payment->financialTransaction->reversalTransaction) }}"
                        >
                            Lihat jurnal reversal
                        </a>
                    @endif
                </div>
            @endif

            @can('student-payments.cancel')
                @if ($payment->status === 'posted')
                    <form
                        method="POST"
                        action="{{ route('finance.student-payments.cancel', $payment) }}"
                        class="space-y-3 rounded-xl border border-rose-200 bg-rose-50 p-4"
                        onsubmit="return confirm('Batalkan pembayaran dan pulihkan sisa tagihan?')"
                    >
                        @csrf
                        @method('PATCH')
                        <x-ui.textarea
                            name="reason"
                            label="Alasan Pembatalan"
                            rows="3"
                            required
                        />
                        <x-ui.button variant="danger" class="w-full">Batalkan Pembayaran</x-ui.button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
@endcomponent
