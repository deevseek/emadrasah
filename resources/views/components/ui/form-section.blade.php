@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->class('card') }}>
    <div class="card-body">
        @if ($title || $description)
            <header class="mb-5 border-b border-slate-100 pb-4">
                @if ($title)
                    <h2 class="text-lg font-bold text-emerald-950">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                @endif
            </header>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            {{ $slot }}
        </div>
    </div>
</section>
