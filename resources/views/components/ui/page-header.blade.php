@props([
    'title',
    'description' => null,
])

<header {{ $attributes->class('flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between') }}>
    <div>
        <h1 class="text-2xl font-black tracking-tight text-emerald-950">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
