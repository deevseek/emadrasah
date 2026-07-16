@props([
    'title' => null,
    'description' => null,
    'padded' => true,
])

<section {{ $attributes->class('card') }}>
    <div @class(['card-body' => $padded])>
        @if ($title || $description)
            <header class="mb-5">
                @if ($title)
                    <h2 class="text-lg font-bold text-emerald-950">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                @endif
            </header>
        @endif

        {{ $slot }}
    </div>
</section>
