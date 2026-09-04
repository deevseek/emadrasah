<x-ui.card>
    <div id="consultation-messages" class="flex max-h-[32rem] min-h-72 flex-col gap-3 overflow-y-auto rounded-xl bg-slate-50 p-4" aria-live="polite">
        @forelse($messages as $message)
            <div data-message-id="{{ $message->id }}" @class(['flex', 'justify-end' => $message->sender_user_id === auth()->id()])>
                <div @class(['max-w-[85%] rounded-2xl px-4 py-3 shadow-sm', 'bg-emerald-900 text-white' => $message->sender_user_id === auth()->id(), 'border border-slate-200 bg-white text-slate-800' => $message->sender_user_id !== auth()->id()])>
                    <p class="mb-1 text-xs font-semibold opacity-75">{{ $message->sender->name }}</p>
                    <p class="whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                    <p class="mt-1 text-right text-[11px] opacity-70">{{ $message->created_at->timezone('Asia/Jakarta')->format('H:i') }}</p>
                </div>
            </div>
        @empty
            <p id="consultation-empty" class="m-auto text-center text-sm text-slate-500">Belum ada pesan. Mulai konsultasi dengan bahasa yang santun dan jelas.</p>
        @endforelse
    </div>

    <form method="post" action="{{ $formAction }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label for="message" class="mb-1 block text-sm font-semibold text-slate-700">Pesan</label>
            <textarea id="message" name="message" rows="3" maxlength="4000" required class="w-full rounded-xl border-slate-300 focus:border-emerald-600 focus:ring-emerald-600" placeholder="Tuliskan pesan konsultasi…">{{ old('message') }}</textarea>
            <p class="mt-1 text-xs text-slate-500">Maksimal 4.000 karakter. Jangan mencantumkan kata sandi atau data rahasia.</p>
        </div>
        <button class="min-h-11 rounded-xl bg-emerald-900 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Kirim pesan</button>
    </form>
</x-ui.card>

@push('scripts')
<script>
(() => {
    const container = document.getElementById('consultation-messages');
    let lastId = Number(container.querySelector('[data-message-id]:last-of-type')?.dataset.messageId || 0);
    const append = (message) => {
        if (container.querySelector(`[data-message-id="${message.id}"]`)) return;
        document.getElementById('consultation-empty')?.remove();
        const row = document.createElement('div');
        row.dataset.messageId = message.id;
        row.className = `flex ${message.mine ? 'justify-end' : ''}`;
        const bubble = document.createElement('div');
        bubble.className = `max-w-[85%] rounded-2xl px-4 py-3 shadow-sm ${message.mine ? 'bg-emerald-900 text-white' : 'border border-slate-200 bg-white text-slate-800'}`;
        const sender = document.createElement('p'); sender.className = 'mb-1 text-xs font-semibold opacity-75'; sender.textContent = message.sender;
        const body = document.createElement('p'); body.className = 'whitespace-pre-wrap text-sm'; body.textContent = message.body;
        const time = document.createElement('p'); time.className = 'mt-1 text-right text-[11px] opacity-70'; time.textContent = message.time;
        bubble.append(sender, body, time); row.append(bubble); container.append(row);
        lastId = Math.max(lastId, Number(message.id)); container.scrollTop = container.scrollHeight;
    };
    container.scrollTop = container.scrollHeight;
    const refresh = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(`{{ $messagesEndpoint }}?after=${lastId}`, {headers: {'Accept': 'application/json'}});
            if (response.ok) (await response.json()).messages.forEach(append);
        } catch (_) { /* Koneksi akan dicoba kembali pada interval berikutnya. */ }
    };
    window.setInterval(refresh, 2500);
})();
</script>
@endpush
