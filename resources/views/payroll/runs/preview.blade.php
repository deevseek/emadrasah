<x-app-layout :title="$title ?? 'Preview Payroll'">
<div class="space-y-6">
@include('payroll.partials.tabs')
<x-ui.page-header :title="'Preview '.$run->run_number" description="Periksa rincian payroll sebelum diajukan untuk persetujuan.">
  <x-slot:primary>@can('payroll-runs.submit')<form method="post" action="{{ route('payroll.runs.submit',$run) }}">@csrf @method('patch')<x-ui.button>Ajukan Payroll</x-ui.button></form>@endcan</x-slot:primary>
</x-ui.page-header>
<x-ui.card>
  <x-ui.table :headers="['Pegawai','Gaji Pokok','Potongan Kehadiran','Gross','Total Potongan','Net','Masalah','Tindakan']">
    @forelse($run->items as $i)
      <tr>
        <td class="font-semibold">{{ $i->employee_name_snapshot }}</td>
        <td class="text-right">Rp {{ number_format((int)$i->base_salary,0,',','.') }}</td>
        <td>{{ collect($i->attendance_snapshot ?? [])->map(fn($v,$k)=>str($k)->replace('_',' ')->headline().': '.$v)->join(', ') ?: '-' }}</td>
        <td class="text-right">Rp {{ number_format((int)$i->gross_salary,0,',','.') }}</td>
        <td class="text-right">Rp {{ number_format((int)$i->total_deductions,0,',','.') }}</td>
        <td class="text-right font-semibold">Rp {{ number_format((int)$i->net_salary,0,',','.') }}</td>
        <td>{{ $i->net_salary < 0 ? 'Gaji bersih negatif' : '-' }}</td>
        <td><x-ui.button variant="secondary" :href="route('payroll.runs.items.show',$i)">Rincian</x-ui.button></td>
      </tr>
    @empty
      <tr><td colspan="8"><x-ui.empty-state title="Tidak ada pegawai berhasil dihitung" description="Periksa profil gaji dan komponen payroll sebelum generate ulang." /></td></tr>
    @endforelse
  </x-ui.table>
</x-ui.card>
</div>
</x-app-layout>
