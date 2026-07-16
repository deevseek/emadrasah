<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-emerald-900">{{ $title }}</h1>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded bg-white p-4 shadow">
            <h2 class="mb-3 font-semibold text-emerald-900">Input Data</h2>
            <form method="post" class="grid gap-3 md:grid-cols-3">
                @csrf
                <input name="name" value="{{ old('name') }}" placeholder="Nama" class="rounded border-gray-300">
                <input name="code" value="{{ old('code') }}" placeholder="Kode" class="rounded border-gray-300">
                <input name="year" value="{{ old('year', now()->year) }}" placeholder="Tahun" class="rounded border-gray-300">
                <input name="month" value="{{ old('month', now()->month) }}" placeholder="Bulan" class="rounded border-gray-300">
                <input name="starts_on" type="date" value="{{ old('starts_on', now()->startOfMonth()->toDateString()) }}" class="rounded border-gray-300">
                <input name="ends_on" type="date" value="{{ old('ends_on', now()->endOfMonth()->toDateString()) }}" class="rounded border-gray-300">
                <button class="rounded bg-emerald-800 px-4 py-2 text-white">Simpan</button>
            </form>
            @if ($errors->any())
                <div class="mt-3 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif
        </section>

        <section class="overflow-x-auto rounded bg-white shadow">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-left text-emerald-900">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Nama/Keterangan</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Diperbarui</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t">
                            <td class="p-3">{{ $item->id }}</td>
                            <td class="p-3">{{ $item->name ?? $item->description ?? $item->code ?? '-' }}</td>
                            <td class="p-3"><span class="rounded bg-emerald-50 px-2 py-1 text-emerald-800">{{ $item->status ?? (($item->is_active ?? false) ? 'aktif' : 'nonaktif') }}</span></td>
                            <td class="p-3">{{ optional($item->updated_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{ $items->links() }}
    </div>
</x-app-layout>
