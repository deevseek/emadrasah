<x-layouts.app title="Konsultasi Wali Kelas">
    <div class="mx-auto max-w-5xl space-y-5">
        <x-ui.page-header title="Konsultasi Wali Kelas" subtitle="Pesan terhubung langsung dengan wali kelas aktif anak Anda." />

        @if($children->count() > 1)
            <x-ui.card>
                <form method="get"><label for="student" class="mb-1 block text-sm font-semibold">Pilih anak</label><select id="student" name="student" onchange="this.form.submit()" class="w-full rounded-xl border-slate-300">@foreach($children as $child)<option value="{{ $child->id }}" @selected($student->id === $child->id)>{{ $child->full_name }}</option>@endforeach</select></form>
            </x-ui.card>
        @endif

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
            <p class="text-sm text-emerald-700">Konsultasi untuk</p>
            <p class="font-bold text-emerald-950">{{ $student->full_name }} · {{ $consultation->classroom->display_name }}</p>
            <p class="mt-1 text-sm text-slate-600">Wali kelas: {{ $consultation->teacher->personnel?->full_name ?? $consultation->teacher->name }}</p>
        </div>

        @include('consultations._chat', ['formAction' => route('parent.consultation.store', $student), 'messagesEndpoint' => route('parent.consultation.messages', $consultation)])
    </div>
</x-layouts.app>
