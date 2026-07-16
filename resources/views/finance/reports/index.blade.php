@component('finance._page', [
    'title' => 'Laporan Keuangan',
    'description' => 'Ringkasan saldo kas dan daftar jurnal berdasarkan periode, tipe, serta status.',
])
    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @forelse ($cashAccounts as $cashAccount)
            <x-ui.stat-card
                :label="$cashAccount->name"
                :value="'Rp '.number_format((float) $cashAccount->current_balance, 0, ',', '.')"
                :description="($cashAccount->chartAccount?->code ?? '-').' — '.($cashAccount->is_active ? 'Aktif' : 'Nonaktif')"
            />
        @empty
            <div class="md:col-span-2 xl:col-span-4">
                <x-ui.empty-state
                    title="Belum ada rekening kas"
                    description="Saldo rekening akan tampil setelah kas atau rekening bank dibuat."
                />
            </div>
        @endforelse
    </div>

    <form method="GET" class="mb-5 grid gap-3 md:grid-cols-5">
        <x-ui.input
            name="date_from"
            label="Tanggal Mulai"
            type="date"
            :value="request('date_from')"
        />
        <x-ui.input
            name="date_to"
            label="Tanggal Selesai"
            type="date"
            :value="request('date_to')"
        />

        <div class="space-y-1.5">
            <label for="transaction_type">Tipe Transaksi</label>
            <select id="transaction_type" name="transaction_type">
                <option value="">Semua tipe</option>
                @foreach ([
                    'cash_in' => 'Kas Masuk',
                    'cash_out' => 'Kas Keluar',
                    'transfer' => 'Transfer',
                    'adjustment' => 'Penyesuaian',
                    'opening_balance' => 'Saldo Awal',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(request('transaction_type') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="space-y-1.5">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                <option value="posted" @selected(request('status') === 'posted')>Posted</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <x-ui.button>Terapkan</x-ui.button>
            <x-ui.button
                type="button"
                variant="secondary"
                :href="route('finance.reports.index')"
            >
                Reset
            </x-ui.button>
        </div>
    </form>

    @if ($transactions->isEmpty())
        <x-ui.empty-state
            title="Tidak ada transaksi"
            description="Tidak ditemukan jurnal yang sesuai dengan filter laporan."
        />
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nomor</th>
                    <th>Tipe</th>
                    <th>Keterangan</th>
                    <th>Nilai</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                        <td class="font-semibold text-emerald-900">{{ $transaction->transaction_number }}</td>
                        <td>{{ str($transaction->transaction_type)->replace('_', ' ')->title() }}</td>
                        <td>{{ str($transaction->description)->limit(70) }}</td>
                        <td>Rp {{ number_format((float) $transaction->lines->sum('debit'), 0, ',', '.') }}</td>
                        <td>
                            <x-ui.badge :variant="$transaction->status === 'posted' ? 'success' : 'muted'">
                                {{ str($transaction->status)->title() }}
                            </x-ui.badge>
                        </td>
                        <td>
                            <a
                                class="btn btn-secondary px-3 py-1.5"
                                href="{{ route('finance.transactions.show', $transaction) }}"
                            >
                                Detail
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.pagination :paginator="$transactions" />
    @endif
@endcomponent
