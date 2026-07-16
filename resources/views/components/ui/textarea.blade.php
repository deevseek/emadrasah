@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'help' => null,
    'rows' => 4,
    'required' => false,
])

@php
    $textareaId = $id ?? $name;
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $textareaId }}">
            {{ $label }}
            @if ($required)
                <span class="text-rose-600">*</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $textareaId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        {{ $attributes }}
    >{{ old($errorKey, $value ?? trim((string) $slot)) }}</textarea>

    @if ($help)
        <p class="text-xs text-slate-500">{{ $help }}</p>
    @endif

    @error($errorKey)
        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
