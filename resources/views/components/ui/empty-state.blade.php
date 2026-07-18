@props(['title' => null, 'description' => null, 'icon' => null, 'primary' => null, 'secondary' => null])
<div {{ $attributes->merge(['class'=>'empty-state']) }}>
  @if($icon)<div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-emerald-50 text-2xl text-emerald-800">{{ $icon }}</div>@endif
  @if($title)<p class="font-bold text-slate-800">{{ $title }}</p>@endif
  @if($description)<p class="mx-auto mt-1 max-w-xl text-sm text-slate-500">{{ $description }}</p>@endif
  @if($primary || $secondary || trim($slot ?? ''))<div class="mt-5 flex flex-col items-stretch justify-center gap-2 sm:flex-row sm:flex-wrap">{{ $secondary }}{{ $primary }}{{ $slot }}</div>@endif
</div>
