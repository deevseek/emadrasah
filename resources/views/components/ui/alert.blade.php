@props([
    'variant' => 'success',
])

@php
    $classes = match ($variant) {
        'danger', 'error' => 'border-rose-200 bg-rose-50 text-rose-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'info' => 'border-sky-200 bg-sky-50 text-sky-800',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-800',
    };
@endphp

<div role="alert" {{ $attributes->class(['rounded-xl border px-4 py-3 text-sm font-medium', $classes]) }}>
    {{ $slot }}
</div>
