<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk E-Madrasah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
    <main class="grid min-h-screen place-items-center px-4">
        <form method="post" action="{{ route('login.store') }}" class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            @csrf

            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-amber-600">Backoffice</p>
            <h1 class="mt-2 text-3xl font-bold text-emerald-950">Masuk E-Madrasah</h1>
            <p class="mt-2 text-sm text-slate-600">Gunakan akun resmi madrasah untuk mengakses sistem.</p>

            @if (session('status'))
                <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mt-6 space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('email')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('password')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="rounded border-slate-300">
                    Ingat perangkat ini
                </label>
            </div>

            <button class="mt-6 w-full rounded-lg bg-emerald-950 px-4 py-3 font-semibold text-white hover:bg-emerald-900">
                Masuk
            </button>

            <a class="mt-4 block text-center text-sm font-medium text-emerald-800" href="{{ route('password.request') }}">
                Lupa password?
            </a>
        </form>
    </main>
</body>
</html>
