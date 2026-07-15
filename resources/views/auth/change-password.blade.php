<x-app-layout>
    <x-slot:title>Ubah Password</x-slot:title>
    <form method="post" action="{{ route('password.change.update') }}" class="max-w-xl rounded-2xl border bg-white p-6 shadow-sm">
        @csrf
        @method('put')
        <div class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700">Password Baru</label>
                <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <button class="mt-6 rounded-lg bg-emerald-950 px-5 py-2 font-semibold text-white">Perbarui Password</button>
    </form>
</x-app-layout>
