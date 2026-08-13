@props(['title', 'description' => null, 'breadcrumbs' => [], 'primary' => null, 'secondary' => null])
<div class="page-header">
  @if($breadcrumbs)<nav class="breadcrumb" aria-label="Breadcrumb">{{ collect($breadcrumbs)->join(' / ') }}</nav>@endif
  <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">@if($description)<p class="max-w-3xl text-sm text-slate-500">{{ $description }}</p>@endif</div>
    @if($primary || $secondary || trim($actions ?? '') || trim((string) $slot))<div class="flex flex-col items-stretch gap-2 sm:flex-row sm:flex-wrap sm:items-center lg:justify-end">{{ $secondary }}{{ $primary }}{{ $actions ?? '' }}{{ $slot }}</div>@endif
  </div>
</div>
