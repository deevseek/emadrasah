<x-layouts.app title="Laporan SPMB" :breadcrumbs="['SPMB','Laporan']">
<div class="index-stack">
<x-ui.page-header title="Laporan SPMB" description="Rekapitulasi dan daftar pendaftar Sistem Penerimaan Murid Baru."/>
<x-ui.card>
<h2 class="text-lg font-bold text-emerald-950">Filter Laporan</h2>
<p class="mb-4 text-sm text-slate-500">Periode mengacu pada tanggal pendaftaran. Rekap dan unduhan mengikuti filter yang diterapkan.</p>
@if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="get" action="{{ route('pmbm.reports.index') }}">
<div class="grid gap-4 md:grid-cols-3"><div><label class="form-label" for="academic_year_id">Tahun Pelajaran</label><select class="input" id="academic_year_id" name="academic_year_id"><option value="">Semua tahun pelajaran</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? '') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
<div><label class="form-label" for="source">Sumber Pendaftaran</label><select class="input" id="source" name="source"><option value="">Semua sumber</option>@foreach(['online'=>'Online','offline'=>'Offline'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
<div><label class="form-label" for="status">Status Pendaftaran</label><select class="input" id="status" name="status"><option value="">Semua status</option>@foreach(\App\Enums\Pmbm\ApplicantStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
<div><label class="form-label" for="gender">Jenis Kelamin</label><select class="input" id="gender" name="gender"><option value="">Semua jenis kelamin</option>@foreach(['male'=>'Laki-laki','female'=>'Perempuan'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['gender'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
<div><label class="form-label" for="date_from">Tanggal Pendaftaran Mulai</label><input class="input" id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div><div><label class="form-label" for="date_to">Tanggal Pendaftaran Akhir</label><input class="input" id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div></div>
<div class="mt-4 flex flex-wrap gap-3"><button class="btn btn-primary" type="submit">Terapkan Filter</button><a class="btn btn-secondary" href="{{ route('pmbm.reports.index') }}">Atur Ulang</a></div>
</form></x-ui.card>
<div class="grid gap-4 sm:grid-cols-3">
<x-ui.stat-card label="Total Pendaftar" :value="number_format($summary['total'],0,',','.')"/>
<x-ui.stat-card label="Tahun Pelajaran" :value="$selectedYear?->name ?? 'Semua tahun'"/>
<x-ui.stat-card label="Data pada Halaman Ini" :value="$applicants->count()"/>
</div>
<x-ui.card><h2 class="mb-3 text-lg font-bold text-emerald-950">Rekapitulasi Status Pendaftaran</h2>
<p class="mb-3 text-sm text-slate-500">Jumlah berdasarkan status terakhir pendaftar, mencakup seluruh hasil filter.</p>
<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($summary['statuses'] as $item)@if($item['total'] > 0)<div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3"><span class="text-sm">{{ $item['label'] }}</span><strong>{{ number_format($item['total'],0,',','.') }}</strong></div>@endif@endforeach</div>
@if(!$summary['total'])<p class="text-sm text-slate-500">Belum ada data sesuai filter.</p>@endif</x-ui.card>
<x-ui.card><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-emerald-950">Daftar Pendaftar</h2><p class="text-sm text-slate-500">Menampilkan {{ $applicants->firstItem() ?? 0 }}–{{ $applicants->lastItem() ?? 0 }} dari {{ $applicants->total() }} pendaftar.</p></div>
@can('pmbm.report.export')<div class="flex flex-wrap gap-2"><a class="btn btn-secondary" href="{{ route('pmbm.reports.print',$filters) }}" target="_blank" rel="noopener">Cetak / Simpan PDF</a><a class="btn btn-secondary" href="{{ route('pmbm.reports.export',$filters) }}">Unduh CSV</a></div>@endcan</div>
<x-ui.table :headers="['No.','Nomor Pendaftaran','Nama Lengkap','Tahun Pelajaran','Tanggal Pendaftaran','Jenis Kelamin','Sumber','Status Pendaftaran']">
@forelse($applicants as $a)<tr><td>{{ $applicants->firstItem()+$loop->index }}</td>@include('pmbm.admin.report-cells')</tr>@empty<tr><td colspan="8" class="py-10 text-center">Tidak ada pendaftar sesuai filter. Silakan ubah filter laporan.</td></tr>@endforelse
</x-ui.table><div class="mt-4">{{ $applicants->links() }}</div>
<p class="mt-4 text-xs text-slate-500">Laporan memuat data administrasi terbatas. Gunakan hanya untuk keperluan penerimaan murid.</p>
</x-ui.card></div></x-layouts.app>
