<x-app-layout :title="$title ?? 'Dashboard Operasional'">
<div class="space-y-6">
  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach($stats as $label => $value)<div class="card"><div class="card-body"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-black text-emerald-950">{{ number_format($value) }}</p></div></div>@endforeach
  </div>
  <div class="grid gap-4 md:grid-cols-2"><div class="card"><div class="card-body"><p class="text-sm text-slate-500">Tahun ajaran aktif</p><p class="text-xl font-bold text-emerald-900">{{ $activeYear?->name ?? 'Belum ditetapkan' }}</p></div></div><div class="card"><div class="card-body"><p class="text-sm text-slate-500">Semester aktif</p><p class="text-xl font-bold text-emerald-900">{{ $activeSemester?->name ?? 'Belum ditetapkan' }}</p></div></div></div>
  <div class="card"><div class="card-body"><div class="mb-4 flex items-center justify-between"><div><h2 class="text-lg font-bold text-emerald-950">Aksi Cepat</h2><p class="text-sm text-slate-500">Tindakan operasional sesuai hak akses.</p></div></div><div class="flex flex-wrap gap-2">
    @can('students.create')<a class="btn btn-primary" href="{{ route('students.create') }}">Tambah Siswa</a>@endcan
    @can('guardians.create')<a class="btn btn-secondary" href="{{ route('guardians.create') }}">Tambah Wali</a>@endcan
    @can('student-enrollments.create')<a class="btn btn-secondary" href="{{ route('student-enrollments.create') }}">Tempatkan Siswa</a>@endcan
    @can('employees.create')<a class="btn btn-secondary" href="{{ route('employees.create') }}">Tambah Pegawai</a>@endcan
    @can('classrooms.create')<a class="btn btn-secondary" href="{{ route('classrooms.create') }}">Tambah Kelas</a>@endcan
    @can('subjects.create')<a class="btn btn-secondary" href="{{ route('subjects.create') }}">Tambah Mata Pelajaran</a>@endcan
  </div></div></div>
  <div class="grid gap-6 xl:grid-cols-2">
    <div class="card"><div class="card-body"><h2 class="mb-4 text-lg font-bold text-emerald-950">Distribusi Siswa per Tingkat</h2>@forelse($studentsByGrade as $grade)<div class="mb-3"><div class="flex justify-between text-sm"><span>{{ $grade->name }}</span><b>{{ $grade->active_students_count }}</b></div><div class="progress mt-1"><span style="width: {{ min(100, $grade->active_students_count * 5) }}%"></span></div></div>@empty<div class="empty-state">Belum ada tingkat kelas.</div>@endforelse</div></div>
    <div class="card"><div class="card-body"><h2 class="mb-4 text-lg font-bold text-emerald-950">Kapasitas dan Keterisian Kelas</h2>@forelse($classrooms as $classroom)@php($filled=$classroom->studentEnrollments->count())<div class="mb-3"><div class="flex justify-between text-sm"><span>{{ $classroom->name }} <span class="text-slate-400">({{ $classroom->capacity }} kursi)</span></span><b>{{ $filled }}/{{ $classroom->capacity }}</b></div><div class="progress mt-1"><span style="width: {{ $classroom->capacity ? min(100, round($filled/$classroom->capacity*100)) : 0 }}%"></span></div></div>@empty<div class="empty-state">Belum ada kelas aktif.</div>@endforelse</div></div>
  </div>
  <div class="grid gap-6 xl:grid-cols-4">
    @foreach(['Login terbaru'=>$latestLogins,'Aktivitas data'=>$latestActivities,'Siswa terbaru'=>$latestStudents,'Penempatan terbaru'=>$latestEnrollments] as $heading=>$collection)<div class="card"><div class="card-body"><h2 class="mb-4 font-bold text-emerald-950">{{ $heading }}</h2><div class="space-y-3">@forelse($collection as $item)<div class="rounded-xl bg-slate-50 p-3 text-sm"><p class="font-semibold text-slate-800">{{ $item->student->name ?? $item->name ?? $item->description ?? $item->email ?? 'Aktivitas' }}</p><p class="text-xs text-slate-500">{{ ($item->attempted_at ?? $item->created_at)?->diffForHumans() }}</p></div>@empty<div class="empty-state">Belum ada data.</div>@endforelse</div></div></div>@endforeach
  </div>
</div>
</x-app-layout>
