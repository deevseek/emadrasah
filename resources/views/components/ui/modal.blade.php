@props([
    'name',
    'title' => null,
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

<dialog
    id="{{ $name }}"
    data-ui-modal
    {{ $attributes->class(['w-[calc(100%-2rem)] rounded-2xl border-0 bg-transparent p-0 backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm', $maxWidthClass]) }}
>
    <section class="card w-full">
        @if ($title)
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-emerald-950">{{ $title }}</h2>
                <form method="dialog">
                    <button type="submit" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">Tutup</span>
                    </button>
                </form>
            </header>
        @endif

        <div class="card-body">
            {{ $slot }}
        </div>
    </section>
</dialog>
