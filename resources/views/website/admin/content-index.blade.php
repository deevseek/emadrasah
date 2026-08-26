<x-layouts.app :title="$meta['label']">
    <x-ui.page-header :title="$meta['label']" description="Kelola konten yang ditampilkan pada website madrasah.">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('website.index')">Kembali</x-ui.button>
            <x-ui.button :href="route('website.content.create', $type)">Tambah {{ $meta['singular'] }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="mt-6" :padding="false">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Judul atau nama</th><th>Status</th><th>Terakhir diperbarui</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    @php
                        $isPublished = $type === 'news' ? $item->status?->value === 'published' : $item->is_active;
                        $status = $type === 'news' ? ($isPublished ? 'Terbit' : 'Draf') : ($isPublished ? 'Tampil' : 'Disembunyikan');
                    @endphp
                    <tr>
                        <td class="font-semibold text-emerald-950">{{ $item->title ?? $item->name }}</td>
                        <td><span @class(['badge', 'badge-success' => $isPublished, 'badge-muted' => ! $isPublished])>{{ $status }}</span></td>
                        <td class="text-slate-500">{{ $item->updated_at->translatedFormat('d M Y') }}</td>
                        <td><div class="table-actions justify-end">
                            <x-ui.button variant="secondary" :href="route('website.content.edit', [$type, $item->id])">Ubah</x-ui.button>
                            <form method="post" action="{{ route('website.content.destroy', [$type, $item->id]) }}" onsubmit="return confirm('Hapus {{ $meta['singular'] }} ini? Tindakan ini tidak dapat dibatalkan.')">@csrf @method('DELETE')<x-ui.button variant="danger" type="submit">Hapus</x-ui.button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><p class="font-semibold text-emerald-950">Belum ada {{ $meta['singular'] }}</p><p class="mt-1 text-sm">Tambahkan konten pertama agar dapat ditampilkan di website.</p><x-ui.button class="mt-4" :href="route('website.content.create', $type)">Tambah sekarang</x-ui.button></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>@endif
    </x-ui.card>
</x-layouts.app>
