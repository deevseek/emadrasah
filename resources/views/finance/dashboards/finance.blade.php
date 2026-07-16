@component('finance._page', ['title' => 'Dashboard Keuangan'])
    <form method="get" class="mb-4 grid gap-3 rounded bg-white p-4 shadow md:grid-cols-4">
        <label class="text-sm font-medium text-emerald-900">Tahun
            <input name="year" type="number" value="{{ $year }}" class="mt-1 w-full rounded border-gray-300">
        </label>
        <label class="text-sm font-medium text-emerald-900">Periode Tagihan
            <select name="billing_period_id" class="mt-1 w-full rounded border-gray-300">
                <option value="">Semua periode</option>
                @foreach ($billingPeriods as $period)
                    <option value="{{ $period->id }}" @selected((int) $periodId === $period->id)>{{ $period->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end gap-2 md:col-span-2">
            <button class="rounded bg-emerald-800 px-4 py-2 text-white">Terapkan Filter</button>
            <a href="{{ route('finance.reports.index') }}" class="rounded border border-emerald-800 px-4 py-2 text-emerald-900">Lihat Laporan</a>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
        <x-ui.stat-card title="Penerimaan Hari Ini" :value="'Rp '.number_format((float) $todayIncome, 0, ',', '.')"/>
        <x-ui.stat-card title="Pengeluaran Hari Ini" :value="'Rp '.number_format((float) $todayExpense, 0, ',', '.')"/>
        <x-ui.stat-card title="Saldo Kas dan Bank" :value="'Rp '.number_format((float) $cashBalance, 0, ',', '.')"/>
        <x-ui.stat-card title="Total Tagihan Aktif" :value="'Rp '.number_format((float) $activeInvoiceTotal, 0, ',', '.')"/>
        <x-ui.stat-card title="Total Pembayaran" :value="'Rp '.number_format((float) $paymentTotal, 0, ',', '.')"/>
        <x-ui.stat-card title="Total Tunggakan" :value="'Rp '.number_format((float) $arrearsTotal, 0, ',', '.')"/>
        <x-ui.stat-card title="Siswa Menunggak" :value="$studentsInArrears"/>
        <x-ui.stat-card title="Payroll Aktif" :value="$activePayrollPeriod?->name ?? 'Tidak ada'"/>
        <x-ui.stat-card title="Payroll Menunggu Persetujuan" :value="$payrollWaitingApproval"/>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <section class="rounded bg-white p-4 shadow">
            <h2 class="font-semibold text-emerald-900">Grafik Penerimaan dan Pengeluaran Bulanan</h2>
            <div class="mt-4 space-y-2">
                @foreach ($monthly as $row)
                    @php($max = max((float) $row['income'], (float) $row['expense'], 1))
                    <div>
                        <div class="mb-1 flex justify-between text-xs text-gray-600"><span>{{ $row['month'] }}</span><span>Rp {{ number_format($row['income'], 0, ',', '.') }} / Rp {{ number_format($row['expense'], 0, ',', '.') }}</span></div>
                        <div class="flex h-3 overflow-hidden rounded bg-gray-100"><div class="bg-emerald-800" style="width: {{ min(100, ($row['income'] / $max) * 100) }}%"></div><div class="bg-amber-500" style="width: {{ min(100, ($row['expense'] / $max) * 100) }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="rounded bg-white p-4 shadow">
            <div class="mb-3 flex items-center justify-between"><h2 class="font-semibold text-emerald-900">Transaksi Terbaru</h2><a class="text-sm text-emerald-800" href="{{ route('finance.transactions.index') }}">Detail</a></div>
            <div class="overflow-x-auto"><table class="min-w-full text-sm"><tbody>@forelse ($recentTransactions as $transaction)<tr class="border-t"><td class="p-2">{{ $transaction->transaction_number }}</td><td class="p-2">{{ $transaction->description }}</td><td class="p-2">{{ $transaction->status }}</td></tr>@empty<tr><td class="p-4 text-center text-gray-500">Belum ada transaksi.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
@endcomponent
