@props([
    'label',
    'value',
    'description' => null,
])

<div {{ $attributes->class('card') }}>
    <div class="card-body">
        <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
        <p class="mt-2 text-3xl font-black tracking-tight text-emerald-950">{{ $value }}</p>
        @if ($description)
            <p class="mt-2 text-xs text-slate-500">{{ $description }}</p>
        @endif
        @if (! $slot->isEmpty())
            <div class="mt-4">{{ $slot }}</div>
        @endif
    </div>
</div>
