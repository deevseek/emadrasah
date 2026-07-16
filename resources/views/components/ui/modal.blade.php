@props([
    'name',
    'title' => null,
    'show' => false,
    'maxWidth' => '2xl',
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        default => 'max-w-2xl',
    };
@endphp

<div
    x-data="{ open: @js($show) }"
    x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
    x-on:close-modal.window="if ($event.detail === @js($name)) open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div class="flex min-h-screen items-center justify-center p-4">
        <button
            type="button"
            aria-label="Tutup modal"
            class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm"
            x-on:click="open = false"
        ></button>

        <section {{ $attributes->class(['card relative z-10 w-full', $maxWidthClass]) }}>
            @if ($title)
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-lg font-bold text-emerald-950">{{ $title }}</h2>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        x-on:click="open = false"
                    >
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">Tutup</span>
                    </button>
                </header>
            @endif

            <div class="card-body">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
