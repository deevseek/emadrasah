@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'help' => null,
    'required' => false,
])

@php
    $selectId = $id ?? $name;
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $selectId }}">
            {{ $label }}
            @if ($required)
                <span class="text-rose-600">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        @required($required)
        {{ $attributes }}
    >
        {{ $slot }}
    </select>

    @if ($help)
        <p class="text-xs text-slate-500">{{ $help }}</p>
    @endif

    @error($errorKey)
        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
