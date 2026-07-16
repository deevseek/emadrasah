<x-app-layout title="Form Pengajuan Izin">
    <div class="mb-6"><p class="text-sm text-slate-500">Absensi / Perizinan Guru</p><h1 class="text-2xl font-semibold text-emerald-950">Form Pengajuan Izin</h1></div>
    <x-ui.card>
        <form class="space-y-4" method="post" action="{{ route('employee-leaves.store') }}" enctype="multipart/form-data">
            @csrf
            @foreach (['starts_at' => 'Mulai', 'ends_at' => 'Selesai'] as $name => $label)
                <label class="block text-sm font-medium text-slate-700">{{ $label }}<input class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" type="date" name="{{ $name }}" value="{{ old($name) }}" required>@error($name)<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
            @endforeach
            <label class="block text-sm font-medium text-slate-700">Jenis<select class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="type">@foreach (\App\Enums\LeaveType::cases() as $type)<option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->value }}</option>@endforeach</select></label>
            <label class="block text-sm font-medium text-slate-700">Alasan<textarea class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" name="reason" required>{{ old('reason') }}</textarea>@error('reason')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
            <label class="block text-sm font-medium text-slate-700">Lampiran<input class="mt-1 w-full rounded-lg border-slate-300 focus:border-emerald-800 focus:ring-emerald-800" type="file" name="attachment">@error('attachment')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
            <button class="rounded-lg bg-emerald-900 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700">Simpan</button>
        </form>
    </x-ui.card>
</x-app-layout>
