<x-app-layout>
    <x-slot:title>{{ $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</x-slot:title>

    <form method="post" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="max-w-3xl rounded-2xl border bg-white p-6 shadow-sm">
        @csrf
        @if ($user->exists)
            @method('put')
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">Nama Lengkap</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2" required>
                @error('email')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                <input id="password" name="password" type="password" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2" @required(! $user->exists)>
                @error('password')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2" @required(! $user->exists)>
            </div>
        </div>

        <fieldset class="mt-6 rounded-xl border border-slate-200 p-4">
            <legend class="px-2 text-sm font-semibold text-slate-700">Role Pengguna</legend>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(collect(old('roles', $user->roles->pluck('name')->all()))->contains($role->name))>
                        <span>{{ $role->display_name ?? Str::headline($role->name) }}</span>
                    </label>
                @endforeach
            </div>
            @error('roles')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="mt-6 flex gap-3">
            <button class="rounded-lg bg-emerald-950 px-5 py-2 font-semibold text-white hover:bg-emerald-900">
                Simpan
            </button>
            <a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-5 py-2 font-semibold text-slate-700">
                Batal
            </a>
        </div>
    </form>
</x-app-layout>
