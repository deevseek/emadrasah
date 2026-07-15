@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'dashboard.view'],
        ['label' => 'Profil Madrasah', 'route' => 'school-profile.edit', 'permission' => 'school-profile.view'],
        ['label' => 'Pengaturan', 'route' => 'settings.index', 'permission' => 'settings.view'],
        ['label' => 'Pengguna', 'route' => 'users.index', 'permission' => 'users.view'],
        ['label' => 'Tingkat Kelas', 'route' => 'grade-levels.index', 'permission' => 'grade-levels.view'],
        ['label' => 'Kelas', 'route' => 'classrooms.index', 'permission' => 'classrooms.view'],
        ['label' => 'Mata Pelajaran', 'route' => 'subjects.index', 'permission' => 'subjects.view'],
        ['label' => 'Pegawai', 'route' => 'employees.index', 'permission' => 'employees.view'],
        ['label' => 'Penugasan Mengajar', 'route' => 'teaching-assignments.index', 'permission' => 'teaching-assignments.view'],
        ['label' => 'Jadwal Pelajaran', 'route' => 'schedules.index', 'permission' => 'schedules.view'],
    ];
@endphp

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' - ' : '' }}{{ config('app.name', 'E-Madrasah') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen md:flex">
        <aside class="bg-emerald-950 text-white md:w-72 p-6">
            <div class="rounded-xl border border-amber-300/30 p-4">
                <p class="text-xs uppercase tracking-[0.25em] text-amber-200">Backoffice</p>
                <p class="mt-2 text-2xl font-bold">E-Madrasah</p>
            </div>

            <nav class="mt-8 space-y-2" aria-label="Navigasi utama">
                @foreach ($navigation as $item)
                    @can($item['permission'])
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'block rounded-lg px-4 py-3 text-sm font-medium transition',
                                'bg-white text-emerald-950 shadow' => request()->routeIs($item['route']),
                                'text-emerald-50 hover:bg-emerald-900' => ! request()->routeIs($item['route']),
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endcan
                @endforeach
            </nav>
        </aside>

        <main class="flex-1">
            <header class="border-b bg-white px-6 py-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Beranda / {{ $title ?? 'Dashboard' }}</p>
                        <h1 class="text-2xl font-semibold text-emerald-950">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg border border-emerald-900 px-4 py-2 text-sm font-semibold text-emerald-950 hover:bg-emerald-50">
                            Keluar
                        </button>
                    </form>
                </div>
            </header>

            <div class="p-6">
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
