@props(['title', 'subtitle' => null])

<x-app-layout :title="$title">
    <div class="space-y-6">
        @if ($subtitle)
            <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <p class="text-sm leading-6 text-slate-600">{{ $subtitle }}</p>
            </div>
        @endif

        {{ $slot }}
    </div>
</x-app-layout>
