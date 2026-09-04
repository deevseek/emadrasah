<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Orang Tua - {{ $schoolProfile->display_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-emerald-950 px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-28 -top-28 h-96 w-96 rounded-full border-[70px] border-amber-300/10"></div>
            <a href="{{ route('public.home') }}" class="relative flex items-center gap-3" aria-label="Kembali ke beranda {{ $schoolProfile->display_name }}">
                <span class="grid h-14 w-14 place-items-center overflow-hidden rounded-2xl bg-white/10 font-bold ring-1 ring-white/20">
                    @if($schoolProfile->logo_url)
                        <img src="{{ $schoolProfile->logo_url }}" class="h-full w-full object-contain" alt="Logo {{ $schoolProfile->display_name }}">
                    @else
                        {{ $schoolProfile->initials }}
                    @endif
                </span>
                <span><strong class="block text-lg">{{ $schoolProfile->display_name }}</strong><small class="text-emerald-200">Portal Orang Tua</small></span>
            </a>

            <div class="relative max-w-xl">
                <p class="text-sm font-bold uppercase tracking-[.25em] text-amber-300">Terhubung dengan pendidikan anak</p>
                <h1 class="mt-5 text-5xl font-bold leading-tight">Pantau perkembangan putra-putri Anda dengan mudah.</h1>
                <p class="mt-6 text-lg leading-8 text-emerald-100">Akses informasi akademik, kehadiran, jadwal, dan administrasi anak dalam satu portal yang aman.</p>
            </div>

            <p class="relative text-sm text-emerald-300">Informasi dalam portal hanya tersedia bagi orang tua atau wali yang telah terdaftar.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <a href="{{ route('public.home') }}" class="mb-10 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-950">← Kembali ke beranda</a>
                <div class="mb-8 lg:hidden">
                    <p class="text-sm font-bold uppercase tracking-[.2em] text-amber-600">{{ $schoolProfile->display_name }}</p>
                    <p class="mt-2 text-sm text-slate-500">Portal Orang Tua</p>
                </div>

                <p class="text-sm font-bold uppercase tracking-[.22em] text-emerald-700">Selamat datang</p>
                <h2 class="mt-3 text-3xl font-bold text-emerald-950 sm:text-4xl">Masuk Portal Orang Tua</h2>
                <p class="mt-3 leading-7 text-slate-600">Gunakan email atau username akun wali yang diberikan oleh madrasah.</p>

                @if (session('status'))
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif

                <form method="post" action="{{ route('parent.login.store') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="login">Email atau Username</label>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" class="mt-2 h-12" placeholder="Masukkan email atau username">
                        @error('login')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password">Password</label>
                            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">Lupa password?</a>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 h-12" placeholder="Masukkan password">
                        @error('password')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-3 font-normal text-slate-600">
                        <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300">
                        Ingat perangkat ini
                    </label>
                    <button class="h-12 w-full rounded-xl bg-emerald-900 px-5 font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-200">Masuk ke Portal</button>
                </form>

                <p class="mt-8 rounded-xl bg-slate-100 p-4 text-center text-sm leading-6 text-slate-600">Belum memiliki akun atau mengalami kendala? Silakan hubungi operator madrasah.</p>
            </div>
        </section>
    </main>
</body>
</html>
