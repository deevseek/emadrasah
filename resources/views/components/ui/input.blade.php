@props(['name', 'label' => null, 'value' => null, 'type' => 'text'])
<label class="block text-sm font-medium text-emerald-950">@if($label)<span>{{ $label }}</span>@endif<input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" {{ $attributes->merge(['class'=>'mt-1']) }}>@error($name)<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
