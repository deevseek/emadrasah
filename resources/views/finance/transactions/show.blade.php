@component('finance._page', [
    'title' => 'Detail Jurnal Transaksi',
    'description' => $transaction->transaction_number,
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
                    'Tanggal' => $transaction->transaction_date?->format('d/m/Y'),
                    'Tipe' => str($transaction->transaction_type)->replace('_', ' ')->title(),
                    'Dibuat Oleh' => $transaction->creator?->name ?? '-',
                    'Diposting Oleh' => $transaction->poster?->name ?? '-',
                ] as $label => $value)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-1 font-semibold text-slate-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</p>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $transaction->description }}</p>
            </div>

            <div>
                <h3 class="mb-3 text-base font-bold text-emerald-950">Baris Jurnal</h3>
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Rekening Kas</th>
                            <th>Keterangan</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transaction->lines as $line)
                            <tr>
                                <td>{{ $line->account?->code }} — {{ $line->account?->name }}</td>
                                <td>{{ $line->cashAccount?->name ?? '-' }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-right">Rp {{ number_format((float) $line->debit, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format((float) $line->credit, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold">
                            <td colspan="3" class="text-right">Total</td>
                            <td class="text-right">Rp {{ number_format((float) $transaction->lines->sum('debit'), 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format((float) $transaction->lines->sum('credit'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </x-ui.table>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
                <span class="text-sm font-semibold text-slate-600">Status</span>
                <x-ui.badge :variant="$transaction->status === 'posted' ? 'success' : 'muted'">
                    {{ str($transaction->status)->title() }}
                </x-ui.badge>
            </div>

            <x-ui.button
                type="button"
                variant="secondary"
                :href="route('finance.transactions.index')"
                class="w-full"
            >
                Kembali
            </x-ui.button>

            @if ($transaction->reversalTransaction)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold">Transaksi telah dibalik.</p>
                    <a
                        class="mt-2 inline-block font-semibold underline"
                        href="{{ route('finance.transactions.show', $transaction->reversalTransaction) }}"
                    >
                        Lihat {{ $transaction->reversalTransaction->transaction_number }}
                    </a>
                </div>
            @endif

            @if ($transaction->status === 'cancelled')
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p class="font-semibold">Alasan Pembatalan</p>
                    <p class="mt-1 whitespace-pre-line">{{ $transaction->cancellation_reason }}</p>
                    <p class="mt-2 text-xs">
                        {{ $transaction->canceller?->name ?? '-' }} · {{ $transaction->cancelled_at?->format('d/m/Y H:i') }}
                    </p>
                </div>
            @endif

            @can('finance-transactions.cancel')
                @if ($transaction->status === 'posted')
                    <form
                        method="POST"
                        action="{{ route('finance.transactions.cancel', $transaction) }}"
                        class="space-y-3 rounded-xl border border-rose-200 bg-rose-50 p-4"
                        onsubmit="return confirm('Buat jurnal reversal untuk transaksi ini?')"
                    >
                        @csrf
                        @method('PATCH')
                        <x-ui.textarea
                            name="reason"
                            label="Alasan Pembatalan"
                            rows="3"
                            required
                        />
                        <x-ui.button variant="danger" class="w-full">Batalkan dan Reversal</x-ui.button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
@endcomponent
