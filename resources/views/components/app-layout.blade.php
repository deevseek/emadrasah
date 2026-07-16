@php
    use App\Models\{AcademicYear,Classroom,Employee,Guardian,GradeLevel,SchoolProfile,Semester,Student,Subject,User};
    use Illuminate\Support\Facades\Gate;
    $profile = SchoolProfile::first();
    $activeYear = AcademicYear::where('is_active', true)->first();
    $activeSemester = Semester::where('is_active', true)->first();
    $user = auth()->user();
    $role = $user?->roles?->first()?->name ?? 'Pengguna';
    $counts = [
        'students' => Student::where('is_active', true)->count(), 'guardians' => Guardian::where('is_active', true)->count(),
        'classrooms' => Classroom::where('is_active', true)->count(), 'employees' => Employee::where('is_active', true)->count(),
        'grade-levels' => GradeLevel::where('is_active', true)->count(), 'subjects' => Subject::where('is_active', true)->count(),
        'users' => User::count(),
    ];
    $navGroups = [
        ['label' => 'Dashboard', 'items' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'permission' => 'dashboard.view', 'icon' => 'M3 13h8V3H3v10Zm10 8h8V3h-8v18ZM3 21h8v-6H3v6Z'],
        ]],
        ['label' => 'Data Akademik', 'items' => [
            ['label' => 'Tingkat Kelas', 'route' => 'grade-levels.index', 'match' => 'grade-levels.*', 'permission' => 'grade-levels.view', 'badge' => $counts['grade-levels']],
            ['label' => 'Kelas/Rombel', 'route' => 'classrooms.index', 'match' => 'classrooms.*', 'permission' => 'classrooms.view', 'badge' => $counts['classrooms']],
            ['label' => 'Mata Pelajaran', 'route' => 'subjects.index', 'match' => 'subjects.*', 'permission' => 'subjects.view', 'badge' => $counts['subjects']],
            ['label' => 'Data Pegawai/Guru', 'route' => 'employees.index', 'match' => 'employees.*', 'permission' => 'employees.view', 'badge' => $counts['employees']],
            ['label' => 'Penugasan Mengajar', 'route' => 'teaching-assignments.index', 'match' => 'teaching-assignments.*', 'permission' => 'teaching-assignments.view'],
            ['label' => 'Jadwal Pelajaran', 'route' => 'schedules.index', 'match' => 'schedules.*', 'permission' => 'schedules.view'],
        ]],
        ['label' => 'Kesiswaan', 'items' => [
            ['label' => 'Data Siswa', 'route' => 'students.index', 'match' => 'students.*', 'permission' => 'students.view', 'badge' => $counts['students']],
            ['label' => 'Data Orang Tua/Wali', 'route' => 'guardians.index', 'match' => 'guardians.*', 'permission' => 'guardians.view', 'badge' => $counts['guardians']],
            ['label' => 'Penempatan Kelas', 'route' => 'student-enrollments.index', 'match' => 'student-enrollments.*', 'permission' => 'student-enrollments.view'],
        ]],
        ['label' => 'Kehadiran', 'items' => [
            ['label' => 'Absensi Saya', 'route' => 'employee-attendances.mine', 'match' => 'employee-attendances.mine', 'permission' => 'employee-attendances.view-own'],
            ['label' => 'Rekap Absensi Guru', 'route' => 'employee-attendances.index', 'match' => 'employee-attendances.index', 'permission' => 'employee-attendances.view'],
            ['label' => 'Perizinan Guru', 'route' => 'employee-leaves.index', 'match' => 'employee-leaves.*', 'permission' => 'employee-leaves.view-own'],
            ['label' => 'Absensi Siswa', 'route' => 'student-attendances.index', 'match' => 'student-attendances.*', 'permission' => 'student-attendances.view'],
        ]],
        ['label' => 'Pembelajaran', 'items' => [
            ['label' => 'Jurnal Mengajar', 'route' => 'teaching-journals.index', 'match' => 'teaching-journals.index', 'permission' => 'teaching-journals.view-own'],
            ['label' => 'Verifikasi Jurnal', 'route' => 'teaching-journals.index', 'match' => 'teaching-journals.*', 'permission' => 'teaching-journals.verify'],
            ['label' => 'Rekap Jurnal', 'route' => 'teaching-journals.index', 'match' => 'teaching-journals.*', 'permission' => 'teaching-journals.export'],
        ]],
        ['label' => 'BTAQ', 'items' => [
            ['label' => 'Dashboard BTAQ', 'route' => 'btaq.dashboard', 'match' => 'btaq.dashboard', 'permission' => 'btaq-reports.view'],
            ['label' => 'Level BTAQ', 'route' => 'btaq-levels.index', 'match' => 'btaq-levels.*', 'permission' => 'btaq-levels.view'],
            ['label' => 'Materi BTAQ', 'route' => 'btaq-materials.index', 'match' => 'btaq-materials.*', 'permission' => 'btaq-materials.view'],
            ['label' => 'Kelompok BTAQ', 'route' => 'btaq-groups.index', 'match' => 'btaq-groups.*', 'permission' => 'btaq-groups.view'],
            ['label' => 'Jurnal BTAQ', 'route' => 'btaq-journals.index', 'match' => 'btaq-journals.*', 'permission' => 'btaq-journals.view-own'],
            ['label' => 'Verifikasi Jurnal BTAQ', 'route' => 'btaq-journals.index', 'match' => 'btaq-journals.*', 'permission' => 'btaq-journals.verify'],
            ['label' => 'Perkembangan Siswa', 'route' => 'btaq.progress', 'match' => 'btaq.progress', 'permission' => 'btaq-reports.view'],
            ['label' => 'Rekap BTAQ', 'route' => 'btaq.recap', 'match' => 'btaq.recap', 'permission' => 'btaq-reports.view'],
        ]],
        ['label' => 'Penilaian', 'items' => [
            ['label' => 'Dashboard Penilaian', 'route' => 'assessments.dashboard', 'match' => 'assessments.dashboard', 'permission' => 'assessment-reports.view'],
            ['label' => 'Komponen Penilaian', 'route' => 'assessment-components.index', 'match' => 'assessment-components.*', 'permission' => 'assessments.view-own'],
            ['label' => 'Input Nilai', 'route' => 'assessment-components.index', 'match' => 'assessment-components.*', 'permission' => 'assessments.update'],
            ['label' => 'Remedial', 'route' => 'assessment-components.index', 'match' => 'assessment-components.*', 'permission' => 'assessments.update'],
            ['label' => 'Rekap Nilai', 'route' => 'assessments.recap', 'match' => 'assessments.recap', 'permission' => 'assessment-reports.view'],
            ['label' => 'Rentang Predikat', 'route' => 'predicate-ranges.index', 'match' => 'predicate-ranges.*', 'permission' => 'predicate-ranges.manage'],
        ]],
        ['label' => 'Rapor', 'items' => [
            ['label' => 'Dashboard Rapor', 'route' => 'report-cards.dashboard', 'match' => 'report-cards.dashboard', 'permission' => 'report-cards.view-class'],
            ['label' => 'Penyusunan Rapor', 'route' => 'report-cards.classes', 'match' => 'report-cards.classes', 'permission' => 'report-cards.view-class'],
            ['label' => 'Verifikasi Rapor', 'route' => 'report-cards.verification', 'match' => 'report-cards.verification', 'permission' => 'report-cards.approve'],
            ['label' => 'Cetak Rapor', 'route' => 'report-cards.classes', 'match' => 'report-cards.classes', 'permission' => 'report-cards.print'],
        ]],

        ['label' => 'Keuangan Siswa', 'items' => [
            ['label' => 'Dashboard Pembayaran', 'route' => 'finance.dashboard.payments', 'match' => 'finance.dashboard.payments', 'permission' => 'student-payments.view'],
            ['label' => 'Jenis Tagihan', 'route' => 'finance.fee-types.index', 'match' => 'finance.fee-types.*', 'permission' => 'fee-types.view'],
            ['label' => 'Periode Tagihan', 'route' => 'finance.billing-periods.index', 'match' => 'finance.billing-periods.*', 'permission' => 'billing-periods.view'],
            ['label' => 'Daftar Tagihan', 'route' => 'finance.student-invoices.index', 'match' => 'finance.student-invoices.*', 'permission' => 'student-invoices.view'],
            ['label' => 'Pembayaran Siswa', 'route' => 'finance.student-payments.index', 'match' => 'finance.student-payments.*', 'permission' => 'student-payments.view'],
            ['label' => 'Potongan/Beasiswa', 'route' => 'finance.student-discounts.index', 'match' => 'finance.student-discounts.*', 'permission' => 'student-discounts.view'],
        ]],
        ['label' => 'Keuangan Bendahara', 'items' => [
            ['label' => 'Dashboard Keuangan', 'route' => 'finance.dashboard.finance', 'match' => 'finance.dashboard.finance', 'permission' => 'finance-reports.view'],
            ['label' => 'Bagan Akun', 'route' => 'finance.chart-accounts.index', 'match' => 'finance.chart-accounts.*', 'permission' => 'finance-accounts.view'],
            ['label' => 'Kas dan Rekening', 'route' => 'finance.cash-accounts.index', 'match' => 'finance.cash-accounts.*', 'permission' => 'cash-accounts.view'],
            ['label' => 'Jurnal Transaksi', 'route' => 'finance.transactions.index', 'match' => 'finance.transactions.*', 'permission' => 'finance-transactions.view'],
            ['label' => 'Laporan Keuangan', 'route' => 'finance.reports.index', 'match' => 'finance.reports.*', 'permission' => 'finance-reports.view'],
        ]],
        ['label' => 'Penggajian', 'items' => [
            ['label' => 'Dashboard Penggajian', 'route' => 'finance.dashboard.payroll', 'match' => 'finance.dashboard.payroll', 'permission' => 'payrolls.view'],
            ['label' => 'Komponen Gaji', 'route' => 'finance.salary-components.index', 'match' => 'finance.salary-components.*', 'permission' => 'salary-components.view'],
            ['label' => 'Struktur Gaji Pegawai', 'route' => 'finance.employee-salaries.index', 'match' => 'finance.employee-salaries.*', 'permission' => 'employee-salaries.view'],
            ['label' => 'Periode Penggajian', 'route' => 'finance.payroll-periods.index', 'match' => 'finance.payroll-periods.*', 'permission' => 'payroll-periods.view'],
            ['label' => 'Proses Penggajian', 'route' => 'finance.payrolls.index', 'match' => 'finance.payrolls.*', 'permission' => 'payrolls.view'],
        ]],
        ['label' => 'Administrasi', 'items' => [
            ['label' => 'Profil Madrasah', 'route' => 'school-profile.edit', 'match' => 'school-profile.*', 'permission' => 'school-profile.view'],
            ['label' => 'Pengaturan Sistem', 'route' => 'settings.index', 'match' => 'settings.*', 'permission' => 'settings.view'],
        ]],
        ['label' => 'Manajemen Akses', 'items' => [
            ['label' => 'Pengguna', 'route' => 'users.index', 'match' => 'users.*', 'permission' => 'users.view', 'badge' => $counts['users']],
        ]],
    ];
    $crumbs = collect(explode('.', Route::currentRouteName() ?? 'dashboard'))->map(fn($p)=>str($p)->replace('-',' ')->headline());
@endphp
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ isset($title) ? $title.' - ' : '' }}{{ $profile?->school_name ?? config('app.name', 'E-Madrasah') }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="app-shell">
<div class="fixed inset-0 z-30 hidden bg-slate-950/50 mobile-sidebar-open:block lg:hidden" onclick="closeMobileSidebar()"></div>
<aside class="app-sidebar">
  <div class="flex items-center gap-3 border-b border-white/10 p-5">
    <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white text-lg font-black text-emerald-800">
      @if($profile?->logo_path)<img src="{{ Storage::url($profile->logo_path) }}" class="h-full w-full object-cover" alt="Logo">@else {{ str($profile?->school_name ?? 'EM')->substr(0,2)->upper() }} @endif
    </div><div class="min-w-0 sidebar-label"><p class="truncate font-bold">{{ $profile?->school_name ?? 'MI Muslimat NU' }}</p><p class="text-xs text-emerald-100">Sistem Informasi Madrasah</p></div>
    <button class="ml-auto rounded-lg p-2 text-emerald-100 hover:bg-white/10 lg:hidden" onclick="closeMobileSidebar()">✕</button>
  </div>
  <nav class="flex-1 space-y-5 overflow-y-auto p-4" aria-label="Navigasi utama">
    @foreach($navGroups as $group)
      @php $visible = collect($group['items'])->filter(fn($i)=>Gate::allows($i['permission'])); @endphp
      @if($visible->isNotEmpty())<div><p class="sidebar-section px-3 pb-2 text-xs font-bold uppercase tracking-wider text-emerald-200/80">{{ $group['label'] }}</p><div class="space-y-1">
        @foreach($visible as $item)<a href="{{ route($item['route']) }}" @class(['nav-link','nav-link-active'=>request()->routeIs($item['match'])])><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="{{ $item['icon'] ?? 'M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Zm4 2v2h8V7H8Zm0 4v2h8v-2H8Zm0 4v2h5v-2H8Z' }}"/></svg><span class="sidebar-label flex-1 truncate">{{ $item['label'] }}</span>@isset($item['badge'])<span class="sidebar-label rounded-full bg-white/10 px-2 py-0.5 text-xs">{{ $item['badge'] }}</span>@endisset</a>@endforeach
      </div></div>@endif
    @endforeach
  </nav>
  <div class="border-t border-white/10 p-4"><div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3"><div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-300 font-bold text-emerald-950">{{ str($user?->name ?? 'U')->substr(0,2)->upper() }}</div><div class="min-w-0 sidebar-user-detail"><p class="truncate text-sm font-semibold">{{ $user?->name }}</p><p class="truncate text-xs text-emerald-100">{{ $role }}</p><div class="mt-2 flex gap-2 text-xs"><a href="{{ route('password.change') }}" class="text-amber-200 hover:text-white">Ganti password</a><form method="post" action="{{ route('logout') }}">@csrf<button class="text-amber-200 hover:text-white">Keluar</button></form></div></div></div></div>
</aside>
<main class="app-main"><header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur"><div class="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between"><div class="flex min-w-0 items-start gap-3"><button class="rounded-xl border border-slate-200 bg-white p-2 lg:hidden" onclick="openMobileSidebar()">☰</button><button class="hidden rounded-xl border border-slate-200 bg-white p-2 lg:block" onclick="toggleSidebar()">☰</button><div><p class="text-xs text-slate-500">Beranda / {{ $crumbs->join(' / ') }}</p><h1 class="text-xl font-bold text-emerald-950 sm:text-2xl">{{ $title ?? 'Dashboard' }}</h1></div></div><div class="flex flex-wrap items-center gap-2 text-xs text-slate-600"><span class="rounded-full bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700">{{ $activeYear?->name ?? 'Tahun ajaran belum aktif' }}</span><span class="rounded-full bg-amber-50 px-3 py-1.5 font-semibold text-amber-700">{{ $activeSemester?->name ?? 'Semester belum aktif' }}</span><span>{{ now()->translatedFormat('d F Y') }}</span><button class="rounded-xl border border-slate-200 p-2 text-slate-500" title="Belum ada notifikasi baru">🔔</button></div></div></header>
<div class="max-w-full p-4 sm:p-6">@if(session('status'))<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif @if($errors->any())<div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">Periksa kembali isian formulir.</div>@endif {{ $slot }}</div></main>
</body></html>
