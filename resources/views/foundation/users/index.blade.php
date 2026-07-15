<x-app-layout>
    <x-slot:title>Pengguna</x-slot:title>

    <section class="rounded-2xl border bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-emerald-950">Daftar Pengguna</h2>
                <p class="text-sm text-slate-500">Filter dan kelola akun backoffice madrasah.</p>
            </div>
            @can('users.create')
                <a href="{{ route('users.create') }}" class="rounded-lg bg-emerald-950 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-900">
                    Tambah Pengguna
                </a>
            @endcan
        </div>

        <form method="get" class="mt-6 grid gap-3 md:grid-cols-4">
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau email" class="rounded-lg border border-slate-300 px-3 py-2 md:col-span-2">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua Status</option>
                <option value="1" @selected(request('status') === '1')>Aktif</option>
                <option value="0" @selected(request('status') === '0')>Nonaktif</option>
            </select>
            <select name="role" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua Role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->display_name ?? Str::headline($role->name) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg border border-emerald-950 px-4 py-2 font-semibold text-emerald-950 md:col-span-4">
                Terapkan Filter
            </button>
        </form>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->roles->pluck('display_name')->filter()->join(', ') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('users.show', $user) }}" class="text-emerald-800">Detail</a>
                                    @can('users.update')
                                        <a href="{{ route('users.edit', $user) }}" class="text-emerald-800">Ubah</a>
                                    @endcan
                                    @can('users.deactivate')
                                        <form method="post" action="{{ route('users.toggle', $user) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="text-amber-700">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada pengguna sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $users->links() }}</div>
    </section>
</x-app-layout>
