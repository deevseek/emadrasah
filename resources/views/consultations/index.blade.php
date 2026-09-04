<x-layouts.app title="Konsultasi Orang Tua">
    <div class="mx-auto max-w-5xl space-y-5">
        <x-ui.page-header title="Konsultasi Orang Tua" subtitle="Pesan orang tua/wali untuk kelas yang menjadi tanggung jawab Anda." />
        <x-ui.card>
            <div class="divide-y divide-slate-200">
                @forelse($consultations as $consultation)
                    <a href="{{ route('consultations.show', $consultation) }}" class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0 hover:text-emerald-800">
                        <div><p class="font-bold">{{ $consultation->student->full_name }}</p><p class="text-sm text-slate-600">Orang tua/wali: {{ $consultation->guardian->name }}</p><p class="mt-1 text-xs text-slate-500">Pesan terakhir {{ $consultation->last_message_at?->diffForHumans() ?? 'belum ada' }}</p></div>
                        @if($consultation->unread_count > 0)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{{ $consultation->unread_count }} baru</span>@endif
                    </a>
                @empty
                    <x-ui.empty-state title="Belum ada konsultasi" description="Pesan dari orang tua/wali kelas Anda akan tampil di sini secara otomatis." />
                @endforelse
            </div>
            <div class="mt-5">{{ $consultations->links() }}</div>
        </x-ui.card>
    </div>
</x-layouts.app>
