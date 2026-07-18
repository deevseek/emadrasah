@props(['name', 'label' => null, 'options' => [], 'value' => null])
<label class="block text-sm font-medium text-emerald-950">
    @if($label)<span>{{ $label }}</span>@endif
    <select name="{{ $name }}" {{ $attributes->merge(['class' => 'mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600']) }}>
        <option value="">Pilih {{ $label ?? $name }}</option>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @error($name)<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
</label>
