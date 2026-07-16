@props([
    'paginator' => null,
])

@if ($paginator && $paginator->hasPages())
    <nav {{ $attributes->class('mt-4') }} aria-label="Navigasi halaman">
        {{ $paginator->withQueryString()->links() }}
    </nav>
@elseif (! $slot->isEmpty())
    <nav {{ $attributes->class('mt-4') }} aria-label="Navigasi halaman">
        {{ $slot }}
    </nav>
@endif
