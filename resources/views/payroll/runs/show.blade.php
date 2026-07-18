<x-app-layout :title="$title ?? 'Detail Proses Payroll'">
<div class="space-y-6">
@include('payroll.partials.tabs')
<x-ui.page-header :title="$run->run_number" description="Detail rekap proses payroll dan daftar slip pegawai." />
<x-ui.card>
  <div class="grid gap-3 text-sm text-slate-600 md:grid-cols-3">
    <div><span class="font-semibold text-slate-700">Status:</span> {{ str($run->status)->replace('_',' ')->headline() }}</div>
    <div><span class="font-semibold text-slate-700">Periode:</span> {{ $run->period?->name ?? '-' }}</div>
    <div><span class="font-semibold text-slate-700">Total Net:</span> Rp {{ number_format((int)$run->total_net,0,',','.') }}</div>
  </div>
</x-ui.card>
<x-ui.card>
  <x-ui.table :headers="['Pegawai','Nomor Slip','Gaji Bersih','Status','Tindakan']">
    @forelse($run->items as $i)
      <tr><td class="font-semibold">{{ $i->employee_name_snapshot }}</td><td>{{ $i->payslip_number ?? '-' }}</td><td class="text-right">Rp {{ number_format((int)$i->net_salary,0,',','.') }}</td><td><x-ui.badge>{{ str($i->status)->replace('_',' ')->headline() }}</x-ui.badge></td><td><x-ui.button variant="secondary" :href="route('payroll.runs.items.show',$i)">Rincian</x-ui.button></td></tr>
    @empty
      <tr><td colspan="5"><x-ui.empty-state title="Belum ada rincian payroll" description="Rincian akan tersedia setelah proses payroll berhasil dihitung." /></td></tr>
    @endforelse
  </x-ui.table>
</x-ui.card>
</div>
</x-app-layout>
