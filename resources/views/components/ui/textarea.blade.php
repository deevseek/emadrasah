@props(['name', 'label' => null])
<label class="block text-sm font-medium text-emerald-950">
    @if($label)<span>{{ $label }}</span>@endif
    <textarea name="{{ $name }}" {{ $attributes->merge(['class' => 'mt-1 min-h-28']) }}>{{ old($name, trim((string) $slot)) }}</textarea>
    @error($name)<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
</label>
