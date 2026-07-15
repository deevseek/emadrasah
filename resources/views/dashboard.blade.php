<x-app-layout>
    <x-slot:title>Dashboard</x-slot:title>

    <section class="rounded-2xl border bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Ringkasan Fondasi</p>
        <h2 class="mt-2 text-2xl font-bold text-emerald-950">{{ $profile?->school_name ?? 'Profil madrasah belum diisi.' }}</h2>
        <p class="mt-2 text-slate-600">Data berikut berasal dari database aplikasi, bukan angka dummy.</p>

        <dl class="mt-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-sm text-slate-500">Total Pengguna</dt>
                <dd class="mt-2 text-2xl font-bold text-emerald-950">{{ $userCount }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-sm text-slate-500">Pengguna Aktif</dt>
                <dd class="mt-2 text-2xl font-bold text-emerald-950">{{ $activeUserCount }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-sm text-slate-500">Login Hari Ini</dt>
                <dd class="mt-2 text-2xl font-bold text-emerald-950">{{ $loginCountToday }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-sm text-slate-500">Pengaturan</dt>
                <dd class="mt-2 text-2xl font-bold text-emerald-950">{{ $settingCount }}</dd>
            </div>
        </dl>
    </section>
</x-app-layout>
