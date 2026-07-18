@php
use Illuminate\Support\Facades\Gate;
$tabs = [
 ['Ringkasan','student-finance.dashboard','student-finance-dashboard.view'],['Jenis Tagihan','student-finance.fee-types.index','student-fee-types.view'],['Tagihan','student-finance.bills.index','student-bills.view'],['Generate SPP','student-finance.generate-spp.create','student-bills.generate-bulk'],['Pembayaran','student-finance.payments.index','student-payments.view'],['Tunggakan','student-finance.arrears.index',['student-arrears.view','student-arrears.view-own-class']],['Diskon/Keringanan','student-finance.discounts.index','student-discounts.view'],['Laporan','student-finance.reports.index','student-finance-reports.view']];
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto rounded-2xl bg-white p-2 ring-1 ring-slate-200">
@foreach($tabs as [$label,$route,$permission]) @if(is_array($permission) ? Gate::any($permission) : Gate::allows($permission))<a href="{{ route($route) }}" @class(['whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold','bg-emerald-800 text-white'=>request()->routeIs($route),'text-emerald-900 hover:bg-emerald-50'=>!request()->routeIs($route)])>{{ $label }}</a>@endif @endforeach
</nav>
