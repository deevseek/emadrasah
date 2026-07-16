<x-module-page title="Materi BTAQ" subtitle="Kelola materi BTAQ aktif berdasarkan level, kategori, dan urutan pembelajaran.">
    <div class="rounded-xl bg-white p-6 shadow">
        @if (session('status'))<div class="mb-4 rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>@endif
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <form class="grid flex-1 gap-3 md:grid-cols-4">
                <input name="q" value="{{ request('q') }}" class="rounded-lg border-slate-300" placeholder="Cari materi">
                <select name="btaq_level_id" class="rounded-lg border-slate-300"><option value="">Semua level</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected((string) request('btaq_level_id') === (string) $level->id)>{{ $level->name }}</option>@endforeach</select>
                <select name="category" class="rounded-lg border-slate-300"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ method_exists($category, 'label') ? $category->label() : $category->value }}</option>@endforeach</select>
                <button class="rounded-lg bg-emerald-900 px-4 py-2 text-white">Filter</button>
            </form>
            <a href="{{ route('btaq-materials.create') }}" class="rounded-lg bg-emerald-800 px-4 py-2 text-white">Tambah Materi</a>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Level</th><th class="px-3 py-2 text-left">Materi</th><th class="px-3 py-2 text-left">Kategori</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Aksi</th></tr></thead><tbody>@forelse($materials as $material)<tr class="border-t"><td class="px-3 py-3">{{ $material->level?->name ?? '-' }}</td><td class="px-3 py-3 font-semibold text-emerald-950">{{ $material->name }}</td><td class="px-3 py-3">{{ is_object($material->category) && method_exists($material->category, 'label') ? $material->category->label() : $material->category }}</td><td class="px-3 py-3"><span class="rounded-full bg-emerald-100 px-2 py-1 text-emerald-900">{{ $material->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="px-3 py-3"><a class="text-emerald-800" href="{{ route('btaq-materials.show', $material) }}">Detail</a> <a class="ml-3 text-emerald-800" href="{{ route('btaq-materials.edit', $material) }}">Edit</a></td></tr>@empty<tr><td class="px-3 py-8 text-center text-slate-500" colspan="5">Belum ada materi BTAQ.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $materials->links() }}</div>
    </div>
</x-module-page>
