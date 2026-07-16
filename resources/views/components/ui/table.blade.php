@props([
    'striped' => false,
])

<div class="table-wrap">
    <table {{ $attributes->class(['data-table', '[&_tbody_tr:nth-child(even)]:bg-slate-50/50' => $striped]) }}>
        {{ $slot }}
    </table>
</div>
