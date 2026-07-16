<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-emerald-900">{{ $title }}</h1></x-slot>
    <form class="mb-4 flex gap-2"><input name="status" value="{{ request('status') }}" placeholder="Status" class="rounded border-gray-300"><button class="rounded bg-emerald-800 px-4 py-2 text-white">Filter</button></form>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($cashAccounts as $cash)
            <div class="rounded bg-white p-4 shadow"><div class="text-sm text-gray-500">{{ $cash->name }}</div><div class="text-lg font-bold text-emerald-900">Rp {{ number_format((float) $cash->current_balance, 2, ',', '.') }}</div></div>
        @endforeach
    </div>
    <div class="mt-4 overflow-x-auto rounded bg-white shadow"><table class="min-w-full text-sm"><thead><tr class="bg-gray-100"><th class="p-3">Tanggal</th><th class="p-3">Nomor</th><th class="p-3">Keterangan</th><th class="p-3">Status</th></tr></thead><tbody>@forelse($transactions as $transaction)<tr class="border-t"><td class="p-3">{{ $transaction->transaction_date->format('d/m/Y') }}</td><td class="p-3">{{ $transaction->transaction_number }}</td><td class="p-3">{{ $transaction->description }}</td><td class="p-3">{{ $transaction->status }}</td></tr>@empty<tr><td colspan="4" class="p-6 text-center text-gray-500">Tidak ada transaksi.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $transactions->links() }}</div>
</x-app-layout>
