<nav class="mb-6 flex gap-2 overflow-x-auto rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-200">
@can('grades.view-own')<a class="rounded-xl px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50" href="{{ route('assessments.my-grades') }}">Nilai Saya</a>@endcan
@can('grade-books.view')<a class="rounded-xl px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50" href="{{ route('assessments.leger') }}">Leger</a>@endcan
@can('report-cards.view-own-class')<a class="rounded-xl px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50" href="{{ route('assessments.report-class') }}">Rapor Kelas Saya</a>@endcan
@can('assessments.view-configuration')<a class="rounded-xl px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50" href="{{ route('assessments.configuration') }}">Konfigurasi</a>@endcan
@can('assessment-reports.view')<a class="rounded-xl px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50" href="{{ route('assessments.reports') }}">Laporan</a>@endcan
</nav>
