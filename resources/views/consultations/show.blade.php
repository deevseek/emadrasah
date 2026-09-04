<x-layouts.app title="Percakapan Konsultasi">
    <div class="mx-auto max-w-5xl space-y-5">
        <x-ui.page-header title="Percakapan Konsultasi" subtitle="Jawab pertanyaan orang tua/wali melalui modul guru." />
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
            <a href="{{ route('consultations.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke daftar konsultasi</a>
            <p class="mt-2 font-bold text-emerald-950">{{ $consultation->student->full_name }} · {{ $consultation->classroom->display_name }}</p>
            <p class="text-sm text-slate-600">Orang tua/wali: {{ $consultation->guardian->name }}</p>
        </div>
        @include('consultations._chat', ['formAction' => route('consultations.store', $consultation), 'messagesEndpoint' => route('consultations.messages', $consultation)])
    </div>
</x-layouts.app>
