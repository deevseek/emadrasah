@php
    $lines = old('lines', [
        ['chart_account_id' => '', 'cash_account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
        ['chart_account_id' => '', 'cash_account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
        ['chart_account_id' => '', 'cash_account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
        ['chart_account_id' => '', 'cash_account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
        ['chart_account_id' => '', 'cash_account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
        ['chart_account_id' => '', 'cash_account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
    ]);
@endphp

@component('finance._page', [
    'title' => 'Buat Jurnal Transaksi',
    'description' => 'Isi minimal dua baris. Total debit dan kredit wajib sama sebelum transaksi diposting.',
])
    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-5">
            {{ $errors->first() }}
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('finance.transactions.store') }}" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input
                name="transaction_date"
                label="Tanggal Transaksi"
                type="date"
                :value="now()->toDateString()"
                required
            />

            <div class="space-y-1.5">
                <label for="transaction_type">Tipe Transaksi <span class="text-rose-600">*</span></label>
                <select id="transaction_type" name="transaction_type" required>
                    @foreach ([
                        'cash_in' => 'Kas Masuk',
                        'cash_out' => 'Kas Keluar',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Penyesuaian',
                        'opening_balance' => 'Saldo Awal',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('transaction_type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('transaction_type')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <x-ui.textarea
                    name="description"
                    label="Keterangan Transaksi"
                    rows="3"
                    required
                />
            </div>
        </div>

        <div>
            <div class="mb-3">
                <h3 class="text-base font-bold text-emerald-950">Baris Jurnal</h3>
                <p class="text-sm text-slate-500">Baris kosong akan diabaikan. Gunakan rekening kas hanya pada baris yang mengubah saldo kas/bank.</p>
            </div>

            <div class="table-wrap">
                <table class="data-table min-w-[1100px]">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Rekening Kas</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                            <th>Keterangan Baris</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $index => $line)
                            <tr>
                                <td class="min-w-64">
                                    <select name="lines[{{ $index }}][chart_account_id]">
                                        <option value="">Pilih akun</option>
                                        @foreach ($chartAccounts as $account)
                                            <option
                                                value="{{ $account->id }}"
                                                @selected((string) ($line['chart_account_id'] ?? '') === (string) $account->id)
                                            >
                                                {{ $account->code }} — {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="min-w-52">
                                    <select name="lines[{{ $index }}][cash_account_id]">
                                        <option value="">Bukan baris kas</option>
                                        @foreach ($cashAccounts as $cashAccount)
                                            <option
                                                value="{{ $cashAccount->id }}"
                                                @selected((string) ($line['cash_account_id'] ?? '') === (string) $cashAccount->id)
                                            >
                                                {{ $cashAccount->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input
                                        name="lines[{{ $index }}][debit]"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value="{{ $line['debit'] ?? '' }}"
                                        class="min-w-36"
                                    >
                                </td>
                                <td>
                                    <input
                                        name="lines[{{ $index }}][credit]"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value="{{ $line['credit'] ?? '' }}"
                                        class="min-w-36"
                                    >
                                </td>
                                <td>
                                    <input
                                        name="lines[{{ $index }}][description]"
                                        value="{{ $line['description'] ?? '' }}"
                                        class="min-w-56"
                                        placeholder="Opsional"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
            <x-ui.button
                type="button"
                variant="secondary"
                :href="route('finance.transactions.index')"
            >
                Batal
            </x-ui.button>
            <x-ui.button>Validasi dan Posting</x-ui.button>
        </div>
    </form>
@endcomponent
