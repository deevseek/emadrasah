<x-layouts.app>
<section class="grid gap-4 md:grid-cols-4">
<div class="border bg-white p-4"><div class="text-sm text-stone-500">Pengguna Aktif</div><div class="mt-2 text-2xl font-bold">{{ number_format($activeUserCount, 0, ',', '.') }}</div></div>
<div class="border bg-white p-4"><div class="text-sm text-stone-500">Total Pengguna</div><div class="mt-2 text-2xl font-bold">{{ number_format($userCount, 0, ',', '.') }}</div></div>
<div class="border bg-white p-4"><div class="text-sm text-stone-500">Login Hari Ini</div><div class="mt-2 text-2xl font-bold">{{ number_format($loginCountToday, 0, ',', '.') }}</div></div>
<div class="border bg-white p-4"><div class="text-sm text-stone-500">Pengaturan</div><div class="mt-2 text-2xl font-bold">{{ number_format($settingCount, 0, ',', '.') }}</div></div>
</section>
<section class="mt-6 border bg-white p-5"><h2 class="font-semibold">Konteks Madrasah</h2><dl class="mt-4 grid gap-3 text-sm md:grid-cols-2"><div><dt class="text-stone-500">Nama Madrasah</dt><dd>{{ $profile?->school_name ?? 'Belum diatur' }}</dd></div><div><dt class="text-stone-500">Tahun Ajaran Aktif</dt><dd>{{ $activeYear?->name ?? 'Belum diaktifkan' }}</dd></div><div><dt class="text-stone-500">Semester Aktif</dt><dd>{{ $activeSemester?->name ?? 'Belum diaktifkan' }}</dd></div><div><dt class="text-stone-500">Zona Waktu</dt><dd>{{ $profile?->timezone ?? 'Asia/Jakarta' }}</dd></div></dl></section>
</x-layouts.app>
