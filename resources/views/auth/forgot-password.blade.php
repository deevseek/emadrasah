<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
    <main class="grid min-h-screen place-items-center px-4">
        <form method="post" action="{{ route('password.email') }}" class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            @csrf
            <h1 class="text-2xl font-bold text-emerald-950">Reset Password</h1>
            <p class="mt-2 text-sm text-slate-600">Masukkan email akun Anda untuk menerima tautan reset password.</p>
            @if (session('status'))
                <div class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            <label for="email" class="mt-6 block text-sm font-semibold text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
            @error('email')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
            <button class="mt-6 w-full rounded-lg bg-emerald-950 px-4 py-3 font-semibold text-white">Kirim Tautan Reset</button>
        </form>
    </main>
</body>
</html>
