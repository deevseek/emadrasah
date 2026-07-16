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

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" class="flex flex-1 flex-col gap-2 sm:max-w-xl sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-ui.input
                    name="search"
                    label="Pencarian"
                    :value="request('search')"
                    :placeholder="$searchPlaceholder ?? 'Cari data'"
                />
            </div>
            <div class="flex gap-2">
                <x-ui.button>Terapkan</x-ui.button>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="url()->current()"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>

        @if (! empty($createRoute))
            @can($managePermission)
                <x-ui.button type="button" :href="$createRoute">
                    {{ $createLabel ?? 'Tambah Data' }}
                </x-ui.button>
            @endcan
        @endif
    </div>

    @if ($items->isEmpty())
        <x-ui.empty-state
            :title="$emptyTitle ?? 'Belum ada data'"
            :description="$emptyDescription ?? 'Tambahkan data pertama untuk memulai.'"
        />
    @else
        <x-ui.table>
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php($cells = $rowBuilder($item))
                    <tr>
                        @foreach ($cells as $cell)
                            <td>
                                @if (is_array($cell) && isset($cell['variant']))
                                    <x-ui.badge :variant="$cell['variant']">
                                        {{ $cell['value'] }}
                                    </x-ui.badge>
                                @elseif (is_array($cell) && isset($cell['html']))
                                    {!! $cell['html'] !!}
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                        @endforeach
                        <td>
                            <div class="flex flex-wrap gap-2">
                                @if (! empty($showRouteName))
                                    @can($viewPermission)
                                        <a
                                            class="btn btn-secondary px-3 py-1.5"
                                            href="{{ route($showRouteName, $item) }}"
                                        >
                                            Detail
                                        </a>
                                    @endcan
                                @endif

                                @if (! empty($editRouteName))
                                    @can($managePermission)
                                        @if (! isset($canEdit) || $canEdit($item))
                                            <a
                                                class="btn btn-secondary px-3 py-1.5"
                                                href="{{ route($editRouteName, $item) }}"
                                            >
                                                Edit
                                            </a>
                                        @endif
                                    @endcan
                                @endif

                                @if (! empty($toggleRouteName))
                                    @can($managePermission)
                                        <form method="POST" action="{{ route($toggleRouteName, $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-secondary px-3 py-1.5">
                                                {{ (bool) $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endcan
                                @endif

                                @if (! empty($destroyRouteName))
                                    @can($managePermission)
                                        @if (! isset($canDelete) || $canDelete($item))
                                            <form
                                                method="POST"
                                                action="{{ route($destroyRouteName, $item) }}"
                                                onsubmit="return confirm('{{ $deleteConfirmation ?? 'Hapus data ini?' }}')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger px-3 py-1.5">Hapus</button>
                                            </form>
                                        @endif
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.pagination :paginator="$items" />
    @endif
@endcomponent
