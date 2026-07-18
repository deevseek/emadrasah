@props(['title' => 'Dashboard'])
@php
    use App\Services\Foundation\SchoolProfileService;
    use Illuminate\Support\Facades\Gate;

    $profile = app(SchoolProfileService::class)->current();
    $user = auth()->user();
    $role = $user?->roles?->pluck('display_name')->filter()->first() ?? $user?->roles?->pluck('name')->first() ?? 'Pengguna';
    $navGroups = [
        ['label' => 'Beranda', 'items' => [['label' => 'Beranda', 'route' => 'dashboard', 'match' => 'dashboard', 'permission' => 'dashboard.view']]],
        ['label' => 'Data Madrasah', 'items' => [
            ['label' => 'Profil Madrasah', 'route' => 'school-profile.edit', 'match' => 'school-profile.*', 'permission' => 'school-profile.view'],
            ['label' => 'Tahun Ajaran', 'route' => 'academic-years.index', 'match' => 'academic-years.*', 'permission' => 'academic-years.view'],
            ['label' => 'Semester', 'route' => 'semesters.index', 'match' => 'semesters.*', 'permission' => 'semesters.view'],
            ['label' => 'Guru & Pegawai', 'route' => 'employees.index', 'match' => 'employees.*', 'permission' => 'employees.view'],
            ['label' => 'Data Siswa', 'route' => 'students.index', 'match' => 'students.*', 'permission' => 'students.view'],
            ['label' => 'Orang Tua/Wali', 'route' => 'guardians.index', 'match' => 'guardians.*', 'permission' => 'guardians.view'],
            ['label' => 'Kelas & Rombel', 'route' => 'classrooms.index', 'match' => 'classrooms.*', 'permission' => 'classrooms.view'],
        ]],
        ['label' => 'Akademik', 'items' => [
            ['label' => 'Mata Pelajaran', 'route' => 'subjects.index', 'match' => 'subjects.*', 'permission' => 'subjects.view'],
            ['label' => 'Penugasan Mengajar', 'route' => 'teaching-assignments.index', 'match' => 'teaching-assignments.*', 'permission' => ['teaching-assignments.view', 'teaching-assignments.view-own']],
            ['label' => 'Jadwal Pelajaran', 'route' => 'schedules.index', 'match' => 'schedules.*', 'permission' => ['schedules.view', 'schedules.view-own']],
            ['label' => 'Jurnal Mengajar', 'route' => 'teaching-journals.index', 'match' => 'teaching-journals.*', 'permission' => ['teaching-journals.view-own', 'teaching-journals.view']],
            ['label' => 'Absensi Siswa', 'route' => 'student-attendances.index', 'match' => 'student-attendances.*', 'permission' => ['student-attendances.view-own-class', 'student-attendances.view', 'student-attendances.report']],
            ['label' => 'Penilaian & Rapor', 'route' => 'assessments.index', 'match' => 'assessments.*', 'permission' => ['grades.view-own', 'grade-books.view-own-class', 'grade-books.view', 'report-cards.view-own-class', 'report-cards.view', 'assessments.view-configuration', 'assessment-reports.view']],
        ]],
        ['label' => 'BTAQ', 'items' => [
            ['label' => 'Dashboard BTAQ', 'route' => 'btaq.index', 'match' => 'btaq.index', 'permission' => ['btaq-reports.view', 'btaq-sessions.view-own']],
            ['label' => 'BTAQ Saya', 'route' => 'btaq.mine', 'match' => 'btaq.mine', 'permission' => ['btaq-sessions.view-own']],
            ['label' => 'Kelompok & Jadwal', 'route' => 'btaq-groups.index', 'match' => 'btaq-groups.*', 'permission' => ['btaq-groups.view', 'btaq-schedules.view']],
            ['label' => 'Jurnal BTAQ', 'route' => 'btaq-sessions.index', 'match' => 'btaq-sessions.*', 'permission' => ['btaq-sessions.view', 'btaq-sessions.view-own']],
            ['label' => 'Perkembangan Siswa', 'route' => 'btaq.progress', 'match' => 'btaq.progress', 'permission' => ['btaq-progress.view', 'btaq-progress.view-own-groups', 'btaq-reports.view']],
            ['label' => 'Materi & Level', 'route' => 'btaq-materials.index', 'match' => 'btaq-materials.*', 'permission' => ['btaq-materials.view', 'btaq-levels.view', 'btaq-programs.view']],
            ['label' => 'Laporan BTAQ', 'route' => 'btaq-reports.index', 'match' => 'btaq-reports.*', 'permission' => ['btaq-reports.view']],
        ]],
        ['label' => 'Kehadiran', 'items' => [
            ['label' => 'Absensi Saya', 'route' => 'employee-attendances.mine', 'match' => 'employee-attendances.mine', 'permission' => ['employee-attendances.view-own', 'employee-attendances.check-in', 'employee-attendances.check-out']],
            ['label' => 'Perizinan Saya', 'route' => 'employee-leaves.index', 'match' => 'employee-leaves.index', 'permission' => ['employee-leaves.view-own', 'employee-leaves.create']],
            ['label' => 'Kehadiran Pegawai', 'route' => 'employee-attendances.index', 'match' => 'employee-attendances.index', 'permission' => 'employee-attendances.view'],
            ['label' => 'Persetujuan Perizinan', 'route' => 'employee-leaves.approvals', 'match' => 'employee-leaves.approvals', 'permission' => ['employee-leaves.approve', 'employee-leaves.reject']],
            ['label' => 'Jadwal Kerja', 'route' => 'work-schedules.index', 'match' => 'work-schedules.*', 'permission' => ['work-schedules.view', 'work-schedules.manage']],
            ['label' => 'Laporan Kehadiran', 'route' => 'attendance-reports.index', 'match' => 'attendance-reports.*', 'permission' => ['employee-attendances.view', 'employee-attendances.export']],
        ]],
        ['label' => 'Keuangan', 'items' => [
            ['label' => 'Keuangan Siswa', 'route' => 'student-finance.dashboard', 'match' => 'student-finance.*', 'permission' => ['student-finance-dashboard.view', 'student-bills.view', 'student-payments.view', 'student-arrears.view', 'student-arrears.view-own-class', 'student-finance-reports.view']],
            ['label' => 'Keuangan Operasional', 'route' => 'operational-finance.dashboard', 'match' => 'operational-finance.*', 'permission' => ['operational-finance-dashboard.view','cash-accounts.view','finance-categories.view','operational-incomes.view','operational-expenses.view','cash-transfers.view','finance-approvals.view','budgets.view','cash-books.view','cash-closings.view','operational-finance-reports.view']],
            ['label' => 'Payroll Pegawai', 'route' => 'payroll.dashboard', 'match' => 'payroll.*', 'permission' => ['payroll-dashboard.view','payroll-runs.view','payroll-components.view','salary-profiles.view']],
            ['label' => 'Slip Gaji Saya', 'route' => 'payroll.payslips.mine', 'match' => 'payroll.payslips.mine*', 'permission' => ['payslips.view-own']],
        ]],
        ['label' => 'Akun & Akses', 'items' => [
            ['label' => 'Pengguna', 'route' => 'users.index', 'match' => 'users.*', 'permission' => 'users.view'],
        ]],
    ];
    $crumbs = collect(explode('.', Route::currentRouteName() ?? 'dashboard'))->map(fn ($p) => str($p)->replace('-', ' ')->headline());
@endphp
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }} - {{ $profile->school_name }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="app-shell">
<div class="app-sidebar-overlay" onclick="closeMobileSidebar()" aria-hidden="true"></div>
<aside class="app-sidebar">
  <div class="flex items-center gap-3 border-b border-white/10 p-5">
    <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white text-lg font-black text-emerald-800">
      @if($profile->logo_path)<img src="{{ Storage::url($profile->logo_path) }}" class="h-full w-full object-cover" alt="Logo {{ $profile->school_name }}">@else {{ str($profile->school_name)->substr(0,2)->upper() }} @endif
    </div>
    <div class="min-w-0 sidebar-label"><p class="truncate font-bold">{{ $profile->school_name }}</p><p class="text-xs text-emerald-100">e-Madrasah</p></div>
    <button type="button" class="ml-auto rounded-lg p-2 text-emerald-100 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-amber-300 lg:hidden" onclick="closeMobileSidebar()" aria-label="Tutup navigasi">✕</button>
  </div>
  <nav class="flex-1 space-y-5 overflow-y-auto p-4" aria-label="Navigasi utama">
    @foreach($navGroups as $group)
      @php $visible = collect($group['items'])->filter(fn ($item) => is_array($item['permission']) ? Gate::any($item['permission']) : Gate::allows($item['permission'])); @endphp
      @if($visible->isNotEmpty())
        <div><p class="sidebar-section px-3 pb-2 text-xs font-bold uppercase tracking-wider text-emerald-200/80">{{ $group['label'] }}</p><div class="space-y-1">
          @foreach($visible as $item)<a href="{{ route($item['route']) }}" onclick="closeMobileSidebar()" @class(['nav-link','nav-link-active'=>request()->routeIs($item['match'])])><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Zm4 2v2h8V7H8Zm0 4v2h8v-2H8Zm0 4v2h5v-2H8Z"/></svg><span class="sidebar-label flex-1 truncate">{{ $item['label'] }}</span></a>@endforeach
        </div></div>
      @endif
    @endforeach
  </nav>
  <div class="border-t border-white/10 p-4"><div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3"><div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-300 font-bold text-emerald-950">{{ str($user?->name ?? 'U')->substr(0,2)->upper() }}</div><div class="min-w-0 sidebar-user-detail"><p class="truncate text-sm font-semibold">{{ $user?->name }}</p><p class="truncate text-xs text-emerald-100">{{ $role }}</p><div class="mt-2 flex gap-2 text-xs"><a href="{{ route('password.change') }}" class="text-amber-200 hover:text-white">Ganti password</a><form method="post" action="{{ route('logout') }}">@csrf<button class="text-amber-200 hover:text-white">Keluar</button></form></div></div></div></div>
</aside>
<main class="app-main"><header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur"><div class="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between"><div class="flex min-w-0 items-start gap-3"><button type="button" class="rounded-xl border border-slate-200 bg-white p-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 lg:hidden" onclick="openMobileSidebar()" aria-label="Buka navigasi">☰</button><button type="button" class="hidden rounded-xl border border-slate-200 bg-white p-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 lg:block" onclick="toggleSidebar()" aria-label="Ringkas navigasi">☰</button><div class="min-w-0"><p class="text-xs text-slate-500">Beranda / {{ $crumbs->join(' / ') }}</p><h1 class="text-xl font-bold text-emerald-950 sm:text-2xl">{{ $title }}</h1></div></div><div class="flex flex-wrap items-center gap-2 text-xs text-slate-600"><span class="rounded-full bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700">{{ $activeYearName ?? 'Tahun ajaran belum aktif' }}</span><span class="rounded-full bg-amber-50 px-3 py-1.5 font-semibold text-amber-700">{{ $activeSemesterName ?? 'Semester belum aktif' }}</span><span>{{ now()->translatedFormat('d F Y') }}</span></div></div></header>
<div class="page-container p-4 sm:p-6">@if(session('status'))<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif @if($errors->any())<div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">Periksa kembali isian formulir.</div>@endif {{ $slot }}</div></main>
</body></html>
