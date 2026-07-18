<x-app-layout :title="$title ?? 'Proses Payroll'">
<div class="space-y-6">
@include('payroll.partials.tabs')
<x-ui.page-header title="Proses Payroll" description="Pantau hasil generate payroll per periode sebelum diajukan untuk persetujuan." />
<x-ui.card>
  <x-ui.table :headers="['Run','Periode','Pegawai','Gross','Potongan','Net','Status','Tindakan']">
    @forelse($runs as $r)
      @php($statusMap=['calculated'=>'Dihitung','submitted'=>'Diajukan','approved'=>'Disetujui','rejected'=>'Ditolak','final'=>'Final','paid'=>'Dibayar'])
      <tr>
        <td class="font-semibold">{{ $r->run_number }}</td>
        <td>{{ $r->period?->name ?? '-' }}</td>
        <td class="text-right">{{ $r->employee_count }}</td>
        <td class="text-right">Rp {{ number_format((int)$r->total_gross,0,',','.') }}</td>
        <td class="text-right">Rp {{ number_format((int)$r->total_deductions,0,',','.') }}</td>
        <td class="text-right font-semibold">Rp {{ number_format((int)$r->total_net,0,',','.') }}</td>
        <td><x-ui.badge :variant="in_array($r->status,['approved','final','paid'],true) ? 'success' : ($r->status === 'rejected' ? 'danger' : 'warning')">{{ $statusMap[$r->status] ?? str($r->status)->replace('_',' ')->headline() }}</x-ui.badge></td>
        <td><div class="table-actions"><x-ui.button variant="secondary" :href="route('payroll.runs.show',$r)">Detail</x-ui.button><x-ui.button variant="outline" :href="route('payroll.runs.preview',$r)">Preview</x-ui.button></div></td>
      </tr>
    @empty
      <tr><td colspan="8"><x-ui.empty-state title="Belum ada proses payroll" description="Generate payroll dari periode aktif untuk menampilkan rekap penghasilan dan potongan pegawai." /></td></tr>
    @endforelse
  </x-ui.table>
  <div class="mt-4">{{ $runs->links() }}</div>
</x-ui.card>
</div>
</x-app-layout>
