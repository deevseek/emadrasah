@props([
    'variant' => 'muted',
])

@php
    $classes = match ($variant) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        'info' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        default => 'badge-muted',
    };
@endphp

<span {{ $attributes->class(['badge', $classes]) }}>
    {{ $slot }}
</span>
