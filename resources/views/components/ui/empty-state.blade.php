@props(['title' => null, 'description' => null, 'action' => null])
<div {{ $attributes->merge(['class'=>'empty-state']) }}>@if($title)<p class="font-bold text-slate-700">{{ $title }}</p>@endif @if($description)<p class="mt-1 text-sm">{{ $description }}</p>@endif <div class="mt-4">{{ $action }}{{ $slot }}</div></div>
