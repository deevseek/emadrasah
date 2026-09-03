@props(['title' => null, 'breadcrumbs' => null, 'displayFlash' => true])
@php
    use Illuminate\Support\Facades\Gate;

    $schoolName = (string) $applicationSettings->get('institution_name', $schoolProfile->display_name);
    $appName = (string) $applicationSettings->get('app_name', config('emadrasah.app_name'));
    $logo = $applicationSettings->assetUrl('primary_logo') ?: $schoolProfile->logo_url;
    $favicon = $applicationSettings->assetUrl('favicon');
    $user = auth()->user();
    $role = $user?->roles?->pluck('display_name')->filter()->first() ?? $user?->roles?->pluck('name')->first() ?? 'Pengguna';
    $navGroups = config('navigation', []);
    $title = filled($title) ? $title : 'Dashboard';
    $crumbs = collect($breadcrumbs ?? ['Beranda']);
@endphp
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} - {{ $appName }}</title>@if($favicon)<link rel="icon" href="{{ $favicon }}">@endif<style>:root{--app-primary:{{ $applicationSettings->get('primary_color', '#047857') }}}</style>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="app-shell" data-sidebar-default="{{ $applicationSettings->get('sidebar_mode', 'expanded') }}">
<div class="app-sidebar-overlay" onclick="closeMobileSidebar()" aria-hidden="true"></div>
<aside class="app-sidebar">
  <div class="flex items-center gap-3 border-b border-white/10 p-5">
    <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white text-lg font-black text-emerald-800">
      @if($logo)<img src="{{ $logo }}" class="h-full w-full object-cover" alt="Logo {{ $schoolName }}">@else {{ str($schoolName)->substr(0,2)->upper() }} @endif
    </div>
    <div class="min-w-0 sidebar-label"><p class="truncate font-bold">{{ $schoolName }}</p><p class="text-xs text-emerald-100">{{ $appName }}</p></div>
    <button type="button" class="ml-auto rounded-lg p-2 text-emerald-100 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-amber-300 lg:hidden" onclick="closeMobileSidebar()" aria-label="Tutup navigasi">✕</button>
  </div>
  <nav class="flex-1 space-y-3 overflow-y-auto p-3" aria-label="Navigasi utama">
    @foreach($navGroups as $group)
      @php $visible = collect($group['items'])->filter(fn ($item) => isset($item['permission_any']) ? collect($item['permission_any'])->contains(fn ($permission) => Gate::allows($permission)) : Gate::allows($item['permission'])); @endphp
      @if($visible->isNotEmpty())
        <div><p class="sidebar-section px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-200/80">{{ $group['label'] }}</p><div class="space-y-0.5">
          @foreach($visible as $item)@php($isActive = request()->routeIs($item['active']))<a href="{{ route($item['route']) }}" onclick="closeMobileSidebar()" @class(['nav-link','nav-link-active'=>$isActive])><x-ui.icon :name="$item['icon']" /><span class="sidebar-label flex-1 truncate">{{ $item['label'] }}</span></a>@endforeach
        </div></div>
      @endif
    @endforeach
  </nav>
  <div class="border-t border-white/10 p-4"><div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3"><div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-300 font-bold text-emerald-950">{{ str($user?->name ?? 'U')->substr(0,2)->upper() }}</div><div class="min-w-0 sidebar-user-detail"><p class="truncate text-sm font-semibold">{{ $user?->name }}</p><p class="truncate text-xs text-emerald-100">{{ $role }}</p><div class="mt-2 flex gap-2 text-xs"><a href="{{ route('password.change') }}" class="text-amber-200 hover:text-white">Ganti password</a><form method="post" action="{{ route('logout') }}">@csrf<button class="text-amber-200 hover:text-white">Keluar</button></form></div></div></div></div>
</aside>
<main class="app-main"><header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur"><div class="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between"><div class="flex min-w-0 items-start gap-3"><button type="button" class="rounded-xl border border-slate-200 bg-white p-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 lg:hidden" onclick="openMobileSidebar()" aria-label="Buka navigasi">☰</button><button type="button" class="hidden rounded-xl border border-slate-200 bg-white p-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 lg:block" onclick="toggleSidebar()" aria-label="Ringkas navigasi">☰</button><div class="min-w-0"><p class="text-xs text-slate-500">{{ $crumbs->join(' / ') }}</p><h1 class="text-xl font-bold text-emerald-950 sm:text-2xl">{{ $title }}</h1></div></div><div class="flex flex-wrap items-center gap-2 text-xs text-slate-600"><span class="rounded-full bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700">{{ $activeAcademicPeriod?->name ?? 'Tahun ajaran belum diatur' }}</span><span class="rounded-full bg-amber-50 px-3 py-1.5 font-semibold text-amber-700">{{ $activeAcademicPeriod?->activeSemester?->display_name ?? 'Semester belum diatur' }}</span><span>{{ now()->translatedFormat('d F Y') }}</span></div></div></header>
<div class="page-container p-4 sm:px-5 sm:py-5 lg:px-6">@if($displayFlash && session('status'))<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif @if($displayFlash && $errors->any())<div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><p class="font-semibold">Periksa kembali isian formulir.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif {{ $slot }}</div></main>
@stack('scripts')
</body></html>
