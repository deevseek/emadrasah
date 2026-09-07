<x-layouts.app title="Pelayanan Email" :breadcrumbs="['Pelayanan', 'Email']">
    <div class="index-stack">
        <x-ui.page-header title="Pelayanan Email" description="Kirim email resmi MI Muslimat NU Demak kepada wali murid, calon peserta didik, instansi, atau penerima lainnya.">
            <x-slot:actions>
                @can('email-service.send')
                    <x-ui.button :href="route('email-service.create')"><x-ui.icon name="plus" /> Tulis Email</x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        <form method="get" class="filter-bar grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
            <x-ui.input name="search" label="Pencarian" :value="request('search')" placeholder="Tujuan, subjek, atau petugas" />
            <x-ui.select name="status" label="Status" :value="request('status')">
                <option value="">Semua status</option>
                @foreach(\App\Enums\OutgoingEmailStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.button type="submit" variant="outline"><x-ui.icon name="filter" /> Terapkan</x-ui.button>
        </form>

        <x-ui.table :headers="['Tanggal', 'Tujuan', 'Subjek', 'Petugas', 'Status', 'Aksi']">
            @forelse($emails as $email)
                <tr>
                    <td class="whitespace-nowrap">{{ $email->created_at->translatedFormat('d M Y H:i') }}</td>
                    <td><span title="{{ implode(', ', $email->to_addresses) }}">{{ str(implode(', ', $email->to_addresses))->limit(55) }}</span></td>
                    <td class="font-medium text-emerald-950">{{ $email->subject }}</td>
                    <td>{{ $email->user?->name ?? 'Pengguna dihapus' }}</td>
                    <td><span class="badge {{ $email->status->badgeClass() }}">{{ $email->status->label() }}</span></td>
                    <td><a class="font-semibold text-emerald-700 hover:underline" href="{{ route('email-service.show', $email) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-ui.empty-state icon="✉" title="Belum ada email keluar" description="Email yang dibuat melalui pelayanan administrasi akan tampil di sini." /></td></tr>
            @endforelse
        </x-ui.table>
        {{ $emails->links() }}
    </div>
</x-layouts.app>
