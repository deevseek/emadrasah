<x-module-page title="Level BTAQ" subtitle="Kelola jenjang pembelajaran BTAQ.">
    <div class="rounded-xl bg-white p-4 shadow">
        <form class="mb-4 grid gap-3 md:grid-cols-3">
            <input name="q" value="{{ request('q') }}" class="rounded border-gray-300" placeholder="Cari level">
            <select name="status" class="rounded border-gray-300"><option value="">Semua status</option><option value="1">Aktif</option><option value="0">Nonaktif</option></select>
            <button class="rounded bg-emerald-900 px-4 py-2 text-white">Filter</button>
        </form>
        <a href="{{ route('btaq-levels.create') }}" class="rounded bg-emerald-800 px-4 py-2 text-white">Tambah Level</a>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead><tr><th>Kode</th><th>Nama</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($levels as $level)<tr class="border-t"><td>{{ $level->code }}</td><td>{{ $level->name }}</td><td>{{ $level->sequence }}</td><td>{{ $level->is_active ? 'Aktif' : 'Nonaktif' }}</td><td><a href="{{ route('btaq-levels.show',$level) }}" class="text-emerald-800">Detail</a></td></tr>@empty<tr><td colspan="5" class="py-6 text-center text-gray-500">Belum ada level BTAQ.</td></tr>@endforelse</tbody></table></div>{{ $levels->links() }}
    </div>
</x-module-page>
