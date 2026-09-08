<x-layouts.app title="Baca Email" :breadcrumbs="['Pelayanan', 'Email', 'Kotak Masuk', 'Baca']">
    <div class="mx-auto max-w-5xl space-y-4">
        <x-ui.page-header title="{{ $email->subject }}" description="Email diterima {{ $email->received_at->diffForHumans() }}.">
            <x-slot:actions>
                <x-ui.button :href="route('email-service.index')" variant="secondary"><x-ui.icon name="back" /> Kembali ke Kotak Masuk</x-ui.button>
                @can('email-service.send')
                    <x-ui.button :href="route('email-service.create', ['to' => $email->from_address, 'subject' => 'Re: '.$email->subject])">Balas</x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <dl class="grid gap-x-6 gap-y-3 text-sm md:grid-cols-[8rem_minmax(0,1fr)]">
                <dt class="font-semibold text-slate-500">Dari</dt><dd><strong>{{ $email->from_name ?: $email->from_address }}</strong>@if($email->from_name)<br>{{ $email->from_address }}@endif</dd>
                <dt class="font-semibold text-slate-500">Kepada</dt><dd>{{ implode(', ', $email->to_addresses) }}</dd>
                <dt class="font-semibold text-slate-500">CC</dt><dd>{{ $email->cc_addresses ? implode(', ', $email->cc_addresses) : '—' }}</dd>
                <dt class="font-semibold text-slate-500">Diterima</dt><dd>{{ $email->received_at->translatedFormat('d F Y H:i') }}</dd>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-4 text-lg font-bold text-emerald-950">Isi Email</h2>
            <div class="whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm leading-7 text-slate-800">{{ $email->body ?: '(Email ini tidak memiliki isi teks.)' }}</div>
        </x-ui.card>

        @if($email->attachments)
            <x-ui.card>
                <h2 class="mb-2 text-lg font-bold text-emerald-950">Lampiran</h2>
                @foreach($email->attachments as $index => $attachment)
                    <div class="flex flex-col gap-2 border-b border-slate-100 py-3 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-semibold text-slate-800">{{ $attachment['original_name'] }}</p><p class="text-xs text-slate-500">{{ number_format(($attachment['size'] ?? 0) / 1024, 1, ',', '.') }} KB</p></div>
                        <x-ui.button :href="route('email-service.incoming.attachment', [$email, $index])" variant="outline">Unduh</x-ui.button>
                    </div>
                @endforeach
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
