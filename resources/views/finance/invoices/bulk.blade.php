<x-app-layout title="Generate SPP">
@include('finance.student.tabs')
<form method="POST" action="{{ route('student-finance.generate-spp.preview') }}" class="grid gap-4 rounded-2xl bg-white p-6 ring-1 ring-slate-200 md:grid-cols-2">@csrf
<x-ui.select name="academic_year_id" label="Tahun Ajaran" :options="$academicYears->pluck('name','id')" />
<x-ui.select name="classroom_id" label="Kelas" :options="$classrooms->pluck('name','id')" />
<x-ui.select name="fee_type_id" label="Jenis Tagihan" :options="$feeTypes->pluck('name','id')" />
<x-ui.input name="billing_month" label="Bulan" type="number" min="1" max="12" />
<x-ui.input name="billing_year" label="Tahun" type="number" value="{{ now()->year }}" />
<x-ui.input name="original_amount" label="Nominal" type="number" />
<x-ui.input name="due_on" label="Jatuh Tempo" type="date" />
<x-ui.textarea name="description" label="Catatan" />
<div class="md:col-span-2"><x-ui.button>Preview Generate</x-ui.button></div>
</form>
</x-app-layout>
