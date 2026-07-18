<x-app-layout title="Keuangan Siswa">
    @include('finance.student.tabs')
    <section class="grid gap-4 md:grid-cols-4">
        <x-ui.stat-card title="Pembayaran Hari Ini" :value="'Rp '.number_format((int) $todayPayments, 0, ',', '.')" />
        <x-ui.stat-card title="Transaksi Hari Ini" :value="$todayTransactions" />
        <x-ui.stat-card title="Pemasukan Bulan Ini" :value="'Rp '.number_format((int) $monthPayments, 0, ',', '.')" />
        <x-ui.stat-card title="Total Sisa Tagihan" :value="'Rp '.number_format((int) $outstanding, 0, ',', '.')" />
        <x-ui.stat-card title="Tagihan Terbit Bulan Ini" :value="'Rp '.number_format((int) $monthBills, 0, ',', '.')" />
        <x-ui.stat-card title="Siswa Menunggak" :value="$arrearStudents" />
        <x-ui.stat-card title="Jatuh Tempo Hari Ini" :value="$dueToday" />
        <x-ui.stat-card title="Dibatalkan Bulan Ini" :value="$cancelledThisMonth" />
    </section>
    <section class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between"><h2 class="font-bold text-emerald-950">Pembayaran Terbaru</h2><a class="text-sm font-semibold text-emerald-700" href="{{ route('student-finance.payments.index') }}">Lihat pembayaran</a></div>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-slate-500"><th class="py-2">Nomor</th><th>Siswa</th><th>Tanggal</th><th class="text-right">Nominal</th><th>Status</th></tr></thead><tbody>@forelse($recentPayments as $payment)<tr class="border-t"><td class="py-2">{{ $payment->payment_number }}</td><td>{{ $payment->student?->name }}</td><td>{{ $payment->payment_date }}</td><td class="text-right">Rp {{ number_format((int) $payment->total_amount, 0, ',', '.') }}</td><td>{{ str($payment->status)->headline() }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada pembayaran siswa.</td></tr>@endforelse</tbody></table></div>
    </section>
</x-app-layout>
