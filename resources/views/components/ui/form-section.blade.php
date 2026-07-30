@props(['title', 'description' => null])
<x-ui.card {{ $attributes }}><div class="mb-5"><h2 class="text-lg font-bold text-emerald-950">{{ $title }}</h2>@if($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif</div>{{ $slot }}</x-ui.card>
