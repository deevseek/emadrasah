<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ config('app.name') }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="bg-stone-50 text-stone-900">
<div class="min-h-screen md:flex">
<aside class="hidden w-72 border-r border-stone-200 bg-emerald-950 text-white md:block"><div class="p-5"><div class="text-sm font-semibold uppercase tracking-wide text-emerald-200">E-Madrasah</div><div class="mt-1 text-lg font-bold">MI Muslimat NU</div></div><nav class="space-y-1 px-3 text-sm"><a class="block rounded px-3 py-2 bg-emerald-900" href="{{ route('dashboard') }}">Dashboard</a><div class="px-3 py-2 text-emerald-200">Akademik</div><div class="px-3 py-2 text-emerald-200">Guru dan Pegawai</div><div class="px-3 py-2 text-emerald-200">Siswa dan Wali</div><div class="px-3 py-2 text-emerald-200">Pengaturan</div><div class="px-3 py-2 text-emerald-200">Audit Sistem</div></nav></aside>
<main class="flex-1"><header class="border-b bg-white"><div class="flex items-center justify-between px-5 py-4"><div><div class="text-xs text-stone-500">Beranda / Dashboard</div><h1 class="text-xl font-semibold">Dashboard</h1></div><form method="post" action="{{ route('logout') }}">@csrf<button class="rounded border px-3 py-2 text-sm">Keluar</button></form></div></header><div class="p-5">{{ $slot }}</div></main>
</div>
</body></html>
