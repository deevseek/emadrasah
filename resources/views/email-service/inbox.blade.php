<x-layouts.app title="Kotak Masuk Email" :breadcrumbs="['Pelayanan', 'Email', 'Kotak Masuk']">
    <div class="index-stack">
        <x-ui.page-header title="Kotak Masuk" description="Baca dan kelola email yang diterima melalui alamat resmi madrasah.">
            <x-slot:actions>
                @can('email-service.send')
                    <x-ui.button :href="route('email-service.create')"><x-ui.icon name="plus" /> Tulis Email</x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        <nav class="flex gap-2 border-b border-slate-200" aria-label="Kotak email">
            <a href="{{ route('email-service.index') }}" aria-current="page" class="border-b-2 border-emerald-700 px-4 py-3 text-sm font-bold text-emerald-800">
                Kotak Masuk
                @if($unreadCount > 0)<span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs">{{ $unreadCount }}</span>@endif
            </a>
            <a href="{{ route('email-service.sent') }}" class="px-4 py-3 text-sm font-semibold text-slate-600 hover:text-emerald-800">Email Keluar</a>
        </nav>

        <form method="get" class="filter-bar grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto] md:items-end">
            <x-ui.input name="search" label="Pencarian" :value="request('search')" placeholder="Pengirim atau subjek" />
            <x-ui.select name="filter" label="Tampilkan" :value="request('filter')">
                <option value="">Semua email</option>
                <option value="unread" @selected(request('filter') === 'unread')>Belum dibaca</option>
            </x-ui.select>
            <x-ui.button type="submit" variant="outline"><x-ui.icon name="filter" /> Terapkan</x-ui.button>
        </form>

        <x-ui.table :headers="['Pengirim', 'Subjek', 'Diterima', 'Lampiran']">
            @forelse($emails as $email)
                <tr class="{{ $email->read_at ? '' : 'bg-emerald-50/60' }}">
                    <td>
                        <a class="block text-slate-800 hover:text-emerald-800" href="{{ route('email-service.incoming.show', $email) }}">
                            <span class="{{ $email->read_at ? 'font-medium' : 'font-bold' }}">{{ $email->from_name ?: $email->from_address }}</span>
                            @if($email->from_name)<span class="block text-xs font-normal text-slate-500">{{ $email->from_address }}</span>@endif
                        </a>
                    </td>
                    <td><a class="{{ $email->read_at ? 'font-medium' : 'font-bold text-emerald-950' }} hover:underline" href="{{ route('email-service.incoming.show', $email) }}">{{ $email->subject }}</a></td>
                    <td class="whitespace-nowrap text-sm text-slate-600">{{ $email->received_at->translatedFormat('d M Y H:i') }}</td>
                    <td>{{ count($email->attachments ?? []) ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><x-ui.empty-state icon="✉" title="Kotak masuk masih kosong" description="Email yang diterima melalui webhook penyedia email akan tampil di sini." /></td></tr>
            @endforelse
        </x-ui.table>
        {{ $emails->links() }}
    </div>
</x-layouts.app>
