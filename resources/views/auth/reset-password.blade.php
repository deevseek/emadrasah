<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Baru</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
    <main class="grid min-h-screen place-items-center px-4">
        <form method="post" action="{{ route('password.store') }}" class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <h1 class="text-2xl font-bold text-emerald-950">Buat Password Baru</h1>
            <label for="email" class="mt-6 block text-sm font-semibold text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', request('email')) }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
            @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <label for="password" class="mt-4 block text-sm font-semibold text-slate-700">Password Baru</label>
            <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
            @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <label for="password_confirmation" class="mt-4 block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
            <button class="mt-6 w-full rounded-lg bg-emerald-950 px-4 py-3 font-semibold text-white">Simpan Password Baru</button>
        </form>
    </main>
</body>
</html>
