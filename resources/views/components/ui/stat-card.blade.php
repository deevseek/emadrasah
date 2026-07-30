@props(['label' => null, 'value' => null])
<section {{ $attributes->merge(['class' => 'card card-body']) }}>
  @if($label)<p class="text-sm font-semibold text-slate-500">{{ $label }}</p>@endif
  @if($value !== null)<p class="mt-2 text-3xl font-black text-emerald-950">{{ $value }}</p>@endif
  {{ $slot }}
</section>
