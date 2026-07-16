@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'id' => null,
    'help' => null,
    'required' => false,
])

@php
    $inputId = $id ?? $name;
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div class="space-y-1.5">
    @if ($type === 'checkbox')
        <label for="{{ $inputId }}" class="flex cursor-pointer items-start gap-3">
            <input
                id="{{ $inputId }}"
                name="{{ $name }}"
                type="checkbox"
                value="1"
                @checked((bool) old($errorKey, $value))
                {{ $attributes->class('mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500') }}
            >
            <span>
                @if ($label)
                    <span class="block text-sm font-semibold text-slate-700">
                        {{ $label }}
                        @if ($required)
                            <span class="text-rose-600">*</span>
                        @endif
                    </span>
                @endif
                @if ($help)
                    <span class="mt-0.5 block text-xs font-normal text-slate-500">{{ $help }}</span>
                @endif
            </span>
        </label>
    @else
        @if ($label)
            <label for="{{ $inputId }}">
                {{ $label }}
                @if ($required)
                    <span class="text-rose-600">*</span>
                @endif
            </label>
        @endif

        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($errorKey, $value) }}"
            @required($required)
            {{ $attributes }}
        >

        @if ($help)
            <p class="text-xs text-slate-500">{{ $help }}</p>
        @endif
    @endif

    @error($errorKey)
        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
