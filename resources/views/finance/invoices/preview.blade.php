<x-app-layout title="Preview Generate SPP">
@include('finance.student.tabs')
<section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200"><h2 class="font-bold text-emerald-950">{{ $students->count() }} siswa akan diproses</h2><p class="text-slate-600">Jenis: {{ $feeType?->name }}. Total rencana Rp {{ number_format((int) $data['original_amount'] * $students->count(),0,',','.') }}</p><form method="POST" action="{{ route('student-finance.generate-spp.store') }}" class="mt-4">@csrf @foreach($data as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach<x-ui.button onclick="return confirm('Generate tagihan SPP sekarang?')">Konfirmasi Generate</x-ui.button></form></section>
</x-app-layout>
