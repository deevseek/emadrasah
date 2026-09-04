<x-app-layout title="Notifikasi" :breadcrumbs="['Beranda', 'Notifikasi']">
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Pembaruan dari seluruh modul yang dapat Anda akses.</p>
        @if(auth()->user()->unreadNotifications()->exists())
            <form method="post" action="{{ route('notifications.read-all') }}">@csrf @method('patch')<x-ui.button type="submit" variant="secondary">Tandai semua dibaca</x-ui.button></form>
        @endif
    </div>
    <x-ui.card>
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                <form method="post" action="{{ route('notifications.read', $notification) }}" class="block">@csrf @method('patch')
                    <button class="flex w-full items-start gap-3 px-5 py-4 text-left hover:bg-emerald-50/60">
                        <span @class(['mt-2 h-2.5 w-2.5 shrink-0 rounded-full', 'bg-emerald-600' => !$notification->read_at, 'bg-slate-300' => $notification->read_at])></span>
                        <span class="min-w-0 flex-1"><span class="text-xs font-bold uppercase tracking-wide text-emerald-700">{{ $notification->data['module_label'] ?? 'Sistem' }}</span><span class="mt-1 block text-sm font-medium text-slate-800">{{ $notification->data['message'] ?? 'Terdapat pembaruan.' }}</span><span class="mt-1 block text-xs text-slate-500">{{ $notification->data['actor'] ? 'Oleh '.$notification->data['actor'].' · ' : '' }}{{ $notification->created_at?->diffForHumans() }}</span></span>
                    </button>
                </form>
            @empty
                <x-ui.empty-state title="Belum ada notifikasi" description="Pembaruan modul akan tampil di sini secara otomatis." />
            @endforelse
        </div>
    </x-ui.card>
    <div class="mt-5">{{ $notifications->links() }}</div>
</x-app-layout>
