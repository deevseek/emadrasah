@props(['variant' => 'primary', 'type' => null, 'href' => null])
@php
$classes = [
    'primary' => 'btn btn-primary',
    'secondary' => 'btn btn-secondary',
    'outline' => 'btn btn-outline',
    'danger' => 'btn btn-danger',
    'ghost' => 'btn btn-ghost',
][$variant] ?? 'btn btn-primary';
@endphp
@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
<button type="{{ $type ?? 'submit' }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
