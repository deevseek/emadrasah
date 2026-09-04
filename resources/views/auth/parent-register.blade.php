<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Portal Orang Tua - {{ $schoolProfile->display_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="grid min-h-screen lg:grid-cols-[.85fr_1.15fr]">
        <section class="relative hidden overflow-hidden bg-emerald-950 px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -left-24 bottom-10 h-80 w-80 rounded-full border-[60px] border-amber-300/10"></div>
            <a href="{{ route('public.home') }}" class="relative flex items-center gap-3">
                <span class="grid h-14 w-14 place-items-center overflow-hidden rounded-2xl bg-white/10 font-bold ring-1 ring-white/20">
                    @if($schoolProfile->logo_url)<img src="{{ $schoolProfile->logo_url }}" class="h-full w-full object-contain" alt="Logo {{ $schoolProfile->display_name }}">@else{{ $schoolProfile->initials }}@endif
                </span>
                <span><strong class="block text-lg">{{ $schoolProfile->display_name }}</strong><small class="text-emerald-200">Portal Orang Tua</small></span>
            </a>
            <div class="relative">
                <p class="text-sm font-bold uppercase tracking-[.25em] text-amber-300">Pendaftaran aman</p>
                <h1 class="mt-5 text-4xl font-bold leading-tight">Satu akun untuk mengikuti perkembangan anak.</h1>
                <ol class="mt-8 space-y-5 text-emerald-100">
                    <li class="flex gap-4"><b class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/10 text-amber-300">1</b><span>Isi identitas orang tua atau wali.</span></li>
                    <li class="flex gap-4"><b class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/10 text-amber-300">2</b><span>Cocokkan NISN dan tanggal lahir anak.</span></li>
                    <li class="flex gap-4"><b class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/10 text-amber-300">3</b><span>Terima konfirmasi akun dan tanda terima SPP melalui email.</span></li>
                </ol>
            </div>
            <p class="relative text-sm text-emerald-300">Data hanya digunakan untuk verifikasi hubungan dengan siswa.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-2xl">
                <a href="{{ route('parent.login') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-950">← Kembali ke halaman masuk</a>
                <p class="text-sm font-bold uppercase tracking-[.22em] text-emerald-700">Portal Orang Tua</p>
                <h2 class="mt-3 text-3xl font-bold text-emerald-950 sm:text-4xl">Daftarkan Akun Anda</h2>
                <p class="mt-3 leading-7 text-slate-600">Akun akan otomatis terhubung apabila identitas anak sesuai dengan data aktif madrasah.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">Periksa kembali data yang ditandai di bawah.</div>
                @endif

                <form method="post" action="{{ route('parent.register.store') }}" class="mt-8 space-y-6">
                    @csrf
                    <fieldset class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 w-full border-b border-slate-200 pb-2 text-sm font-bold uppercase tracking-wide text-emerald-900">Data Orang Tua/Wali</legend>
                        <div class="sm:col-span-2"><label for="name">Nama Lengkap</label><input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-2 h-12" placeholder="Nama sesuai identitas">@error('name')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="email">Email Aktif</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 h-12" placeholder="nama@email.com">@error('email')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="phone_number">Nomor Telepon</label><input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" required autocomplete="tel" class="mt-2 h-12" placeholder="081234567890">@error('phone_number')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                        <div class="sm:col-span-2"><label for="relationship">Hubungan dengan Anak</label><select id="relationship" name="relationship" required class="mt-2 h-12"><option value="">Pilih hubungan</option>@foreach($relationships as $relationship)<option value="{{ $relationship->value }}" @selected(old('relationship') === $relationship->value)>{{ $relationship->label() }}</option>@endforeach</select>@error('relationship')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    </fieldset>

                    <fieldset class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 w-full border-b border-slate-200 pb-2 text-sm font-bold uppercase tracking-wide text-emerald-900">Verifikasi Data Anak</legend>
                        <div><label for="nisn">NISN Anak</label><input id="nisn" name="nisn" inputmode="numeric" value="{{ old('nisn') }}" required class="mt-2 h-12" placeholder="Masukkan NISN">@error('nisn')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="birth_date">Tanggal Lahir Anak</label><input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" required max="{{ now()->subDay()->format('Y-m-d') }}" class="mt-2 h-12">@error('birth_date')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    </fieldset>

                    <fieldset class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 w-full border-b border-slate-200 pb-2 text-sm font-bold uppercase tracking-wide text-emerald-900">Keamanan Akun</legend>
                        <div><label for="password">Password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="mt-2 h-12" placeholder="Minimal 8 karakter">@error('password')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="password_confirmation">Konfirmasi Password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="mt-2 h-12" placeholder="Ulangi password"></div>
                    </fieldset>

                    <label class="flex items-start gap-3 font-normal text-slate-600"><input name="terms" type="checkbox" value="1" required @checked(old('terms')) class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300"><span>Saya menyatakan data yang diisi benar dan menyetujui penggunaannya untuk menghubungkan akun dengan data anak.</span></label>
                    @error('terms')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    <button class="h-12 w-full rounded-xl bg-emerald-900 px-5 font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-200">Daftar dan Hubungkan Akun</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
