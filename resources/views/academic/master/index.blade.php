<x-app-layout :title="$title">
    <div class="space-y-6">
        <form class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-4" method="get">
            <input name="q" value="{{ request('q') }}" placeholder="Cari data" class="rounded-lg border-slate-300">
            <select name="status" class="rounded-lg border-slate-300"><option value="">Semua status</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option></select>
            <select name="academic_year_id" class="rounded-lg border-slate-300"><option value="">Semua tahun ajaran</option>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id')==$year->id)>{{ $year->name }}</option>@endforeach</select>
            <button class="rounded-lg bg-emerald-950 px-4 py-2 font-semibold text-white">Filter</button>
        </form>
        <div class="rounded-xl border bg-white p-4">
            <div class="mb-4 flex items-center justify-between"><h2 class="text-lg font-semibold text-emerald-950">Daftar {{ $title }}</h2>@can($key.'.create')<a href="{{ route($key.'.create') }}" class="rounded-lg bg-emerald-950 px-4 py-2 text-sm font-semibold text-white">Tambah</a>@endcan</div>
            @if($items->isEmpty())<div class="rounded-lg bg-slate-50 p-8 text-center text-slate-500">Belum ada data.</div>@else
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-100 text-left"><tr><th class="p-3">Nama</th><th class="p-3">Kode/Info</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr></thead><tbody>
            @foreach($items as $item)<tr class="border-t"><td class="p-3 font-medium">{{ $item->name ?? ($item->employee->name ?? '-') }}</td><td class="p-3">{{ $item->code ?? $item->employee_number ?? ($item->classroom->name ?? '-') }}</td><td class="p-3"><span class="rounded-full px-3 py-1 text-xs {{ $item->is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-700' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="p-3"><div class="flex gap-2">@can($key.'.update')<a class="text-emerald-800 underline" href="{{ route($key.'.edit', $item) }}">Edit</a>@endcan @can($key.'.delete')<form method="post" action="{{ route($key.'.destroy', $item) }}" onsubmit="return confirm('Nonaktifkan atau hapus data ini?')">@csrf @method('DELETE')<button class="text-red-700 underline">Hapus</button></form>@endcan</div></td></tr>@endforeach
            </tbody></table></div><div class="mt-4">{{ $items->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
