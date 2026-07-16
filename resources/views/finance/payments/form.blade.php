@php
    $allocations = old('allocations', array_fill(0, 6, [
        'student_invoice_id' => '',
        'amount' => '',
    ]));
@endphp

@component('finance._page', [
    'title' => 'Pembayaran Siswa',
    'description' => 'Pilih siswa, rekening penerima, lalu alokasikan pembayaran ke satu atau beberapa tagihan.',
])
    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-5">
            {{ $errors->first() }}
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('finance.student-payments.store') }}" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="student_id">Siswa <span class="text-rose-600">*</span></label>
                <select id="student_id" name="student_id" required>
                    <option value="">Pilih siswa</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected((string) old('student_id') === (string) $student->id)>
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
                @error('student_id')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input
                name="payment_date"
                label="Tanggal Pembayaran"
                type="date"
                :value="now()->toDateString()"
                required
            />

            <div class="space-y-1.5">
                <label for="cash_account_id">Rekening Penerima</label>
                <select id="cash_account_id" name="cash_account_id">
                    <option value="">Gunakan rekening kas aktif utama</option>
                    @foreach ($cashAccounts as $cashAccount)
                        <option value="{{ $cashAccount->id }}" @selected((string) old('cash_account_id') === (string) $cashAccount->id)>
                            {{ $cashAccount->name }} — Rp {{ number_format((float) $cashAccount->current_balance, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                @error('cash_account_id')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="payment_method">Metode Pembayaran <span class="text-rose-600">*</span></label>
                <select id="payment_method" name="payment_method" required>
                    @foreach ([
                        'tunai' => 'Tunai',
                        'transfer_bank' => 'Transfer Bank',
                        'qris' => 'QRIS',
                        'virtual_account' => 'Virtual Account',
                        'lainnya' => 'Lainnya',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_method', 'tunai') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input
                name="reference_number"
                label="Nomor Referensi"
                :value="old('reference_number')"
                placeholder="Opsional, misalnya nomor transfer"
            />

            <x-ui.input
                name="total_amount"
                label="Total Pembayaran"
                type="number"
                min="0"
                step="0.01"
                :value="old('total_amount')"
                required
            />

            <div class="md:col-span-2">
                <x-ui.textarea
                    name="notes"
                    label="Catatan"
                    rows="3"
                />
            </div>
        </div>

        <div>
            <div class="mb-3">
                <h3 class="text-base font-bold text-emerald-950">Alokasi Tagihan</h3>
                <p class="text-sm text-slate-500">
                    Pilih hanya tagihan milik siswa yang dipilih. Jumlah seluruh alokasi wajib sama dengan total pembayaran.
                </p>
            </div>

            <div class="table-wrap">
                <table class="data-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th>Tagihan</th>
                            <th>Sisa Tagihan</th>
                            <th>Nominal Alokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allocations as $index => $allocation)
                            <tr>
                                <td class="min-w-[480px]">
                                    <select name="allocations[{{ $index }}][student_invoice_id]">
                                        <option value="">Pilih tagihan</option>
                                        @foreach ($invoices as $invoice)
                                            <option
                                                value="{{ $invoice->id }}"
                                                data-student-id="{{ $invoice->student_id }}"
                                                @selected((string) ($allocation['student_invoice_id'] ?? '') === (string) $invoice->id)
                                            >
                                                {{ $invoice->student?->name }} — {{ $invoice->invoice_number }} — {{ $invoice->feeType?->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="whitespace-nowrap text-slate-600">
                                    @php
                                        $selectedInvoice = $invoices->firstWhere(
                                            'id',
                                            (int) ($allocation['student_invoice_id'] ?? 0),
                                        );
                                    @endphp
                                    {{ $selectedInvoice
                                        ? 'Rp '.number_format((float) $selectedInvoice->outstanding_amount, 0, ',', '.')
                                        : '-' }}
                                </td>
                                <td>
                                    <input
                                        name="allocations[{{ $index }}][amount]"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value="{{ $allocation['amount'] ?? '' }}"
                                        placeholder="0"
                                        class="min-w-44"
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
                :href="route('finance.student-payments.index')"
            >
                Batal
            </x-ui.button>
            <x-ui.button>Validasi dan Posting Pembayaran</x-ui.button>
        </div>
    </form>
@endcomponent
