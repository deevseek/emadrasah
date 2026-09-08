<x-layouts.app title="Detail Email" :breadcrumbs="['Pelayanan', 'Email', 'Detail']">
    <div class="mx-auto max-w-5xl space-y-4">
        <x-ui.page-header title="Detail Email" description="Informasi dan status pengiriman email pelayanan.">
            <x-slot:actions>
                <x-ui.button :href="route('email-service.sent')" variant="secondary"><x-ui.icon name="back" /> Kembali</x-ui.button>
                @if($email->status === \App\Enums\OutgoingEmailStatus::Draft)
                    @can('email-service.send')
                        <form method="post" action="{{ route('email-service.send', $email) }}">@csrf<x-ui.button type="submit">Kirim Email</x-ui.button></form>
                    @endcan
                @endif
                @if($email->status === \App\Enums\OutgoingEmailStatus::Failed)
                    @can('email-service.send')
                        <form method="post" action="{{ route('email-service.resend', $email) }}">@csrf<x-ui.button type="submit">Kirim Ulang</x-ui.button></form>
                    @endcan
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if($email->status === \App\Enums\OutgoingEmailStatus::Failed)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                <p class="font-bold">Email belum berhasil dikirim.</p>
                <p class="mt-1">{{ $email->error_message }}</p>
            </div>
        @endif

        <x-ui.card>
            <dl class="grid gap-x-6 gap-y-4 text-sm md:grid-cols-[10rem_minmax(0,1fr)]">
                <dt class="font-semibold text-slate-500">From</dt><dd><strong>{{ config('mail.from.name') }}</strong><br>{{ config('mail.from.address') }}</dd>
                <dt class="font-semibold text-slate-500">To</dt><dd>{{ implode(', ', $email->to_addresses) }}</dd>
                <dt class="font-semibold text-slate-500">CC</dt><dd>{{ $email->cc_addresses ? implode(', ', $email->cc_addresses) : '—' }}</dd>
                <dt class="font-semibold text-slate-500">BCC</dt><dd>{{ $email->bcc_addresses ? implode(', ', $email->bcc_addresses) : '—' }}</dd>
                <dt class="font-semibold text-slate-500">Subjek</dt><dd class="font-bold text-emerald-950">{{ $email->subject }}</dd>
                <dt class="font-semibold text-slate-500">Petugas pengirim</dt><dd>{{ $email->user?->name ?? 'Pengguna dihapus' }}</dd>
                <dt class="font-semibold text-slate-500">Waktu dibuat</dt><dd>{{ $email->created_at->translatedFormat('d F Y H:i') }}</dd>
                <dt class="font-semibold text-slate-500">Waktu terkirim</dt><dd>{{ $email->sent_at?->translatedFormat('d F Y H:i') ?? '—' }}</dd>
                <dt class="font-semibold text-slate-500">Status</dt><dd><span class="badge {{ $email->status->badgeClass() }}">{{ $email->status->label() }}</span></dd>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-4 text-lg font-bold text-emerald-950">Isi Email</h2>
            <div class="whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm leading-7 text-slate-800">{{ $email->body }}</div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-4 text-lg font-bold text-emerald-950">Lampiran</h2>
            @forelse($email->attachments ?? [] as $index => $attachment)
                <div class="flex flex-col gap-2 border-b border-slate-100 py-3 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-semibold text-slate-800">{{ $attachment['original_name'] }}</p><p class="text-xs text-slate-500">{{ number_format(($attachment['size'] ?? 0) / 1024, 1, ',', '.') }} KB</p></div>
                    <x-ui.button :href="route('email-service.attachment', [$email, $index])" variant="outline">Unduh</x-ui.button>
                </div>
            @empty
                <p class="text-sm text-slate-500">Tidak ada lampiran.</p>
            @endforelse
        </x-ui.card>
    </div>
</x-layouts.app>
