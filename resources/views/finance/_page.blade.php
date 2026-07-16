<x-app-layout>
    <div class="space-y-6">
        <x-ui.page-header :title="$title" :description="$description ?? ''" />
        <x-ui.card>
            {{ $slot }}
        </x-ui.card>
    </div>
</x-app-layout>
