@component('finance._page', [
    'title' => $title,
    'description' => $description ?? null,
])
    @if (session('status'))
        <x-ui.alert class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-5">
            {{ $errors->first() }}
        </x-ui.alert>
    @endif

    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($details as $label => $value)
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-1 whitespace-pre-line font-semibold text-slate-800">{{ filled($value) ? $value : '-' }}</p>
                </div>
            @endforeach
        </div>

        <aside class="space-y-3">
            @if (! empty($status))
                <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
                    <span class="text-sm font-semibold text-slate-600">Status</span>
                    <x-ui.badge :variant="$status['variant'] ?? 'muted'">
                        {{ $status['label'] }}
                    </x-ui.badge>
                </div>
            @endif

            <x-ui.button type="button" variant="secondary" :href="$indexRoute" class="w-full">
                Kembali
            </x-ui.button>

            @if (! empty($editRoute) && ($canEdit ?? true))
                @can($managePermission)
                    <x-ui.button type="button" variant="secondary" :href="$editRoute" class="w-full">
                        Edit
                    </x-ui.button>
                @endcan
            @endif

            @if (! empty($toggleRoute))
                @can($managePermission)
                    <form method="POST" action="{{ $toggleRoute }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button variant="secondary" class="w-full">
                            {{ ($isActive ?? false) ? 'Nonaktifkan' : 'Aktifkan' }}
                        </x-ui.button>
                    </form>
                @endcan
            @endif

            @foreach ($workflowActions ?? [] as $action)
                @can($action['permission'])
                    <form
                        method="POST"
                        action="{{ $action['url'] }}"
                        class="space-y-3 rounded-xl border border-slate-200 p-4"
                        @if (! empty($action['confirm']))
                            onsubmit="return confirm('{{ $action['confirm'] }}')"
                        @endif
                    >
                        @csrf
                        @method($action['method'] ?? 'PATCH')

                        @foreach ($action['fields'] ?? [] as $field)
                            @if (($field['type'] ?? 'text') === 'textarea')
                                <x-ui.textarea
                                    :name="$field['name']"
                                    :label="$field['label']"
                                    :required="$field['required'] ?? false"
                                    :rows="$field['rows'] ?? 3"
                                />
                            @elseif (($field['type'] ?? 'text') === 'select')
                                <div class="space-y-1.5">
                                    <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                                    <select id="{{ $field['name'] }}" name="{{ $field['name'] }}" @required($field['required'] ?? false)>
                                        @foreach ($field['options'] ?? [] as $option)
                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endforeach

                        <x-ui.button :variant="$action['variant'] ?? 'primary'" class="w-full">
                            {{ $action['label'] }}
                        </x-ui.button>
                    </form>
                @endcan
            @endforeach

            @if (! empty($destroyRoute) && ($canDelete ?? true))
                @can($managePermission)
                    <form
                        method="POST"
                        action="{{ $destroyRoute }}"
                        onsubmit="return confirm('{{ $deleteConfirmation ?? 'Hapus data ini?' }}')"
                    >
                        @csrf
                        @method('DELETE')
                        <x-ui.button variant="danger" class="w-full">Hapus</x-ui.button>
                    </form>
                @endcan
            @endif
        </aside>
    </div>
@endcomponent
