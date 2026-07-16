@props([
    'type' => 'submit',
    'variant' => 'primary',
    'href' => null,
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        default => 'btn-primary',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['btn', $variantClass]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['btn', $variantClass]) }}>
        {{ $slot }}
    </button>
@endif
