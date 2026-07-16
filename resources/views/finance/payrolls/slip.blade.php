<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-emerald-900">Slip Gaji</h1></x-slot>
    <article class="mx-auto max-w-3xl rounded bg-white p-6 shadow print:shadow-none">
        <h2 class="text-center text-lg font-bold text-emerald-900">Slip Gaji Pegawai</h2>
        <p class="text-center text-sm text-gray-600">Periode {{ $payroll->period->name }}</p>
        <dl class="mt-6 grid grid-cols-2 gap-3 text-sm"><dt>Pegawai</dt><dd>{{ $payroll->employee->name }}</dd><dt>Hadir</dt><dd>{{ $payroll->attendance_present }}</dd><dt>Netto</dt><dd class="font-bold">Rp {{ number_format((float) $payroll->net_salary, 2, ',', '.') }}</dd></dl>
        <table class="mt-6 w-full text-sm"><thead><tr class="bg-gray-100"><th class="p-2 text-left">Komponen</th><th class="p-2 text-right">Nilai</th></tr></thead><tbody>@foreach($payroll->items as $item)<tr class="border-t"><td class="p-2">{{ $item->component_name_snapshot }}</td><td class="p-2 text-right">{{ number_format((float) $item->amount, 2, ',', '.') }}</td></tr>@endforeach</tbody></table>
        <button onclick="window.print()" class="mt-6 rounded bg-emerald-800 px-4 py-2 text-white print:hidden">Cetak</button>
    </article>
</x-app-layout>
