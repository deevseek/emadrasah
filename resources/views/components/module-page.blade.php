@props(['title','subtitle'=>null])
<x-layouts.app :title="$title">
    <div class="space-y-6">
        <div class="rounded-2xl bg-emerald-950 px-6 py-5 text-white shadow">
            <p class="text-sm text-emerald-100">eMadrasah</p>
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-emerald-100">{{ $subtitle }}</p>
            @endif
        </div>
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif
        {{ $slot }}
    </div>
</x-layouts.app>
