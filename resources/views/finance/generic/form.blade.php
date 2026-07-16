@component('finance._page', [
    'title' => $title,
    'description' => $description ?? null,
])
    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-5">
            Periksa kembali data form. Terdapat {{ $errors->count() }} kesalahan validasi.
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if (strtoupper($method ?? 'POST') !== 'POST')
            @method($method)
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($fields as $field)
                @php
                    $name = $field['name'];
                    $type = $field['type'] ?? 'text';
                    $value = old($name, $field['value'] ?? null);
                    $spanClass = ($field['span'] ?? 1) === 2 ? 'md:col-span-2' : '';
                @endphp

                <div class="{{ $spanClass }} space-y-1.5">
                    @if ($type === 'checkbox')
                        <input type="hidden" name="{{ $name }}" value="0">
                        <label for="{{ $name }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
                            <input
                                id="{{ $name }}"
                                name="{{ $name }}"
                                type="checkbox"
                                value="1"
                                @checked((bool) $value)
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"
                            >
                            <span>
                                <span class="block font-semibold text-slate-700">{{ $field['label'] }}</span>
                                @if (! empty($field['help']))
                                    <span class="mt-0.5 block text-xs font-normal text-slate-500">{{ $field['help'] }}</span>
                                @endif
                            </span>
                        </label>
                    @elseif ($type === 'select')
                        <label for="{{ $name }}">
                            {{ $field['label'] }}
                            @if (! empty($field['required']))
                                <span class="text-rose-600">*</span>
                            @endif
                        </label>
                        <select id="{{ $name }}" name="{{ $name }}" @required(! empty($field['required']))>
                            @if (array_key_exists('placeholder', $field))
                                <option value="">{{ $field['placeholder'] }}</option>
                            @endif
                            @foreach ($field['options'] ?? [] as $option)
                                <option
                                    value="{{ $option['value'] }}"
                                    @selected((string) $value === (string) $option['value'])
                                >
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    @elseif ($type === 'textarea')
                        <label for="{{ $name }}">
                            {{ $field['label'] }}
                            @if (! empty($field['required']))
                                <span class="text-rose-600">*</span>
                            @endif
                        </label>
                        <textarea
                            id="{{ $name }}"
                            name="{{ $name }}"
                            rows="{{ $field['rows'] ?? 4 }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @required(! empty($field['required']))
                        >{{ $value }}</textarea>
                    @else
                        <label for="{{ $name }}">
                            {{ $field['label'] }}
                            @if (! empty($field['required']))
                                <span class="text-rose-600">*</span>
                            @endif
                        </label>
                        <input
                            id="{{ $name }}"
                            name="{{ $name }}"
                            type="{{ $type }}"
                            value="{{ $value }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if (isset($field['min'])) min="{{ $field['min'] }}" @endif
                            @if (isset($field['max'])) max="{{ $field['max'] }}" @endif
                            @if (isset($field['step'])) step="{{ $field['step'] }}" @endif
                            @required(! empty($field['required']))
                        >
                    @endif

                    @if (! empty($field['help']) && $type !== 'checkbox')
                        <p class="text-xs text-slate-500">{{ $field['help'] }}</p>
                    @endif

                    @error($name)
                        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
            <x-ui.button type="button" variant="secondary" :href="$cancelRoute">
                Batal
            </x-ui.button>
            <x-ui.button>{{ $submitLabel ?? 'Simpan' }}</x-ui.button>
        </div>
    </form>
@endcomponent
