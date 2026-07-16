@props([
    'title' => 'Belum ada data',
    'description' => null,
])

<div {{ $attributes->class('empty-state') }}>
    <h3 class="font-bold text-slate-700">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-1 max-w-xl text-sm text-slate-500">{{ $description }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-4 flex justify-center">
            {{ $slot }}
        </div>
    @endif
</div>
