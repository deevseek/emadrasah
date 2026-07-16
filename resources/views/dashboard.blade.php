<x-app-layout :title="$title">
<div class="space-y-6">
  <section class="rounded-3xl bg-emerald-950 p-6 text-white shadow-sm">
    <p class="text-sm text-emerald-100">Dashboard dasar</p>
    <h2 class="mt-2 text-2xl font-black">{{ $profile->school_name }}</h2>
    <p class="mt-2 max-w-3xl text-sm text-emerald-50">Ringkasan ini hanya menggunakan data fondasi: profil madrasah, tahun ajaran, semester, pengguna, dan aktivitas perubahan.</p>
  </section>

  <section class="grid gap-4 md:grid-cols-3">
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Tahun ajaran aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ $activeYear?->name ?? 'Belum ditetapkan' }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Semester aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ $activeSemester?->name ?? 'Belum ditetapkan' }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pengguna aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeUsers) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pegawai aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeEmployees) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('employees.index') }}">Buka Guru & Pegawai</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pegawai tanpa akun</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($employeesWithoutAccount) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Data pegawai belum lengkap</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($incompleteEmployees) }}</p></div></div>
  </section>

  <section class="card"><div class="card-body"><h2 class="text-lg font-bold text-emerald-950">Tindakan yang perlu diperhatikan</h2><div class="mt-4 grid gap-3">
    @unless($profileComplete)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('school-profile.edit') }}">Profil madrasah belum lengkap — Lengkapi sekarang</a>@endunless
    @unless($activeYear)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('academic-years.index') }}">Belum ada tahun ajaran aktif — Aktifkan tahun ajaran</a>@endunless
    @unless($activeSemester)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('semesters.index') }}">Semester aktif belum ditentukan — Atur semester</a>@endunless
    @if($inactiveUsers > 0)<a class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700" href="{{ route('users.index', ['status' => '0']) }}">{{ $inactiveUsers }} pengguna tidak aktif — Lihat pengguna</a>@endif
    @if($profileComplete && $activeYear && $activeSemester && $inactiveUsers === 0)<div class="empty-state">Tidak ada tindakan fondasi yang mendesak.</div>@endif
  </div></div></section>

  <section class="card"><div class="card-body"><h2 class="mb-4 text-lg font-bold text-emerald-950">Aktivitas fondasi terbaru</h2><div class="space-y-3">@forelse($latestActivities as $activity)<div class="rounded-xl bg-slate-50 p-3 text-sm"><p class="font-semibold text-slate-800">{{ $activity->description }}</p><p class="text-xs text-slate-500">{{ $activity->created_at?->diffForHumans() }}</p></div>@empty<div class="empty-state">Belum ada aktivitas perubahan fondasi.</div>@endforelse</div></div></section>
</div>
</x-app-layout>
