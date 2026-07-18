@props(['title', 'description' => null, 'breadcrumbs' => [], 'primary' => null, 'secondary' => null])
<div class="page-header">
  @if($breadcrumbs)<nav class="breadcrumb" aria-label="Breadcrumb">{{ collect($breadcrumbs)->join(' / ') }}</nav>@endif
  <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div class="min-w-0"><h2 class="text-2xl font-black text-emerald-950 sm:text-3xl">{{ $title }}</h2>@if($description)<p class="mt-1 max-w-3xl text-sm text-slate-600">{{ $description }}</p>@endif</div>
    @if($primary || $secondary || trim($actions ?? ''))<div class="flex flex-wrap gap-2">{{ $secondary }}{{ $primary }}{{ $actions ?? '' }}</div>@endif
  </div>
</div>
