<x-app-layout>
    <x-slot:title>Pengaturan</x-slot:title>

    <div class="space-y-6">
        @foreach ($settings->getCollection()->groupBy('group') as $group => $items)
            <section class="rounded-2xl border bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-emerald-950">{{ Str::headline($group) }}</h2>
                    <p class="text-sm text-slate-500">Kelola konfigurasi {{ Str::lower(Str::headline($group)) }}.</p>
                </div>

                <div class="space-y-4">
                    @foreach ($items as $setting)
                        <form method="post" action="{{ route('settings.update', $setting) }}" class="rounded-xl border border-slate-200 p-4">
                            @csrf
                            @method('put')

                            <label for="setting-{{ $setting->id }}" class="block text-sm font-semibold text-slate-800">
                                {{ Str::headline(str_replace('_', ' ', $setting->key)) }}
                            </label>
                            <p class="mt-1 text-xs text-slate-500">Kode: {{ $setting->group }}.{{ $setting->key }}</p>

                            <div class="mt-3 flex flex-col gap-3 md:flex-row">
                                <input
                                    id="setting-{{ $setting->id }}"
                                    name="value"
                                    value="{{ old('value', $setting->value) }}"
                                    class="flex-1 rounded-lg border border-slate-300 px-3 py-2"
                                >
                                <button class="rounded-lg bg-emerald-950 px-4 py-2 font-semibold text-white hover:bg-emerald-900">
                                    Simpan
                                </button>
                            </div>

                            @error('value')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </form>
                    @endforeach
                </div>
            </section>
        @endforeach

        {{ $settings->links() }}
    </div>
</x-app-layout>
