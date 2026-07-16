@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'dashboard.view'],
        ['label' => 'Profil Madrasah', 'route' => 'school-profile.edit', 'permission' => 'school-profile.view'],
        ['label' => 'Pengaturan', 'route' => 'settings.index', 'permission' => 'settings.view'],
        ['label' => 'Pengguna', 'route' => 'users.index', 'permission' => 'users.view'],
        ['label' => 'Tingkat Kelas', 'route' => 'grade-levels.index', 'permission' => 'grade-levels.view'],
        ['label' => 'Kelas', 'route' => 'classrooms.index', 'permission' => 'classrooms.view'],
        ['label' => 'Mata Pelajaran', 'route' => 'subjects.index', 'permission' => 'subjects.view'],
        ['label' => 'Pegawai', 'route' => 'employees.index', 'permission' => 'employees.view'],
        ['label' => 'Penugasan Mengajar', 'route' => 'teaching-assignments.index', 'permission' => 'teaching-assignments.view'],
        ['label' => 'Jadwal Pelajaran', 'route' => 'schedules.index', 'permission' => 'schedules.view'],
        ['label' => 'KEHADIRAN', 'heading' => true],
        ['label' => 'Absensi Saya', 'route' => 'employee-attendances.mine', 'permission' => 'employee-attendances.view-own'],
        ['label' => 'Rekap Absensi Guru', 'route' => 'employee-attendances.index', 'permission' => 'employee-attendances.view'],
        ['label' => 'Perizinan Guru', 'route' => 'employee-leaves.index', 'permission' => 'employee-leaves.view-own'],
        ['label' => 'Absensi Siswa', 'route' => 'student-attendances.index', 'permission' => 'student-attendances.view'],
        ['label' => 'PEMBELAJARAN', 'heading' => true],
        ['label' => 'Jurnal Mengajar', 'route' => 'teaching-journals.index', 'permission' => 'teaching-journals.view-own'],
        ['label' => 'Verifikasi Jurnal', 'route' => 'teaching-journals.index', 'permission' => 'teaching-journals.verify'],
        ['label' => 'Rekap Jurnal', 'route' => 'teaching-journals.index', 'permission' => 'teaching-journals.export'],

        ['label' => 'BTAQ', 'heading' => true],
        ['label' => 'Dashboard BTAQ', 'route' => 'btaq.dashboard', 'permission' => 'btaq-reports.view'],
        ['label' => 'Level BTAQ', 'route' => 'btaq-levels.index', 'permission' => 'btaq-levels.view'],
        ['label' => 'Materi BTAQ', 'route' => 'btaq-materials.index', 'permission' => 'btaq-materials.view'],
        ['label' => 'Kelompok BTAQ', 'route' => 'btaq-groups.index', 'permission' => 'btaq-groups.view'],
        ['label' => 'Jurnal BTAQ', 'route' => 'btaq-journals.index', 'permission' => 'btaq-journals.view-own'],
        ['label' => 'Verifikasi Jurnal BTAQ', 'route' => 'btaq-journals.index', 'permission' => 'btaq-journals.verify'],
        ['label' => 'Perkembangan Siswa', 'route' => 'btaq.progress', 'permission' => 'btaq-reports.view'],
        ['label' => 'Rekap BTAQ', 'route' => 'btaq.recap', 'permission' => 'btaq-reports.view'],
        ['label' => 'PENILAIAN', 'heading' => true],
        ['label' => 'Dashboard Penilaian', 'route' => 'assessments.dashboard', 'permission' => 'assessment-reports.view'],
        ['label' => 'Komponen Penilaian', 'route' => 'assessment-components.index', 'permission' => 'assessments.view-own'],
        ['label' => 'Input Nilai', 'route' => 'assessment-components.index', 'permission' => 'assessments.update'],
        ['label' => 'Remedial', 'route' => 'assessment-components.index', 'permission' => 'assessments.update'],
        ['label' => 'Rekap Nilai', 'route' => 'assessments.recap', 'permission' => 'assessment-reports.view'],
        ['label' => 'Rentang Predikat', 'route' => 'predicate-ranges.index', 'permission' => 'predicate-ranges.manage'],

        ['label' => 'KEUANGAN SISWA', 'heading' => true],
        ['label' => 'Dashboard Pembayaran', 'route' => 'finance.dashboard.payments', 'permission' => 'student-payments.view'],
        ['label' => 'Jenis Tagihan', 'route' => 'finance.fee-types.index', 'permission' => 'fee-types.view'],
        ['label' => 'Daftar Tagihan', 'route' => 'finance.student-invoices.index', 'permission' => 'student-invoices.view'],
        ['label' => 'Pembayaran Siswa', 'route' => 'finance.student-payments.create', 'permission' => 'student-payments.create'],
        ['label' => 'Riwayat Pembayaran', 'route' => 'finance.student-payments.index', 'permission' => 'student-payments.view'],
        ['label' => 'KEUANGAN BENDAHARA', 'heading' => true],
        ['label' => 'Dashboard Keuangan', 'route' => 'finance.dashboard.finance', 'permission' => 'finance-reports.view'],
        ['label' => 'Jurnal Transaksi', 'route' => 'finance.dashboard.finance', 'permission' => 'finance-transactions.view'],
        ['label' => 'Laporan Keuangan', 'route' => 'finance.dashboard.finance', 'permission' => 'finance-reports.view'],
        ['label' => 'PENGGAJIAN', 'heading' => true],
        ['label' => 'Dashboard Penggajian', 'route' => 'finance.dashboard.payroll', 'permission' => 'payrolls.view'],

        ['label' => 'RAPOR', 'heading' => true],
        ['label' => 'Dashboard Rapor', 'route' => 'report-cards.dashboard', 'permission' => 'report-cards.view-class'],
        ['label' => 'Penyusunan Rapor', 'route' => 'report-cards.classes', 'permission' => 'report-cards.view-class'],
        ['label' => 'Verifikasi Rapor', 'route' => 'report-cards.verification', 'permission' => 'report-cards.approve'],
        ['label' => 'Cetak Rapor', 'route' => 'report-cards.classes', 'permission' => 'report-cards.print'],

    ];
@endphp

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' - ' : '' }}{{ config('app.name', 'E-Madrasah') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen md:flex">
        <aside class="bg-emerald-950 text-white md:w-72 p-6">
            <div class="rounded-xl border border-amber-300/30 p-4">
                <p class="text-xs uppercase tracking-[0.25em] text-amber-200">Backoffice</p>
                <p class="mt-2 text-2xl font-bold">E-Madrasah</p>
            </div>

            <nav class="mt-8 space-y-2" aria-label="Navigasi utama">
                @foreach ($navigation as $item)
                    @if($item['heading'] ?? false)
                        <p class="px-4 pt-4 text-xs font-bold uppercase tracking-widest text-amber-200">{{ $item['label'] }}</p>
                        @continue
                    @endif
                    @can($item['permission'])
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'block rounded-lg px-4 py-3 text-sm font-medium transition',
                                'bg-white text-emerald-950 shadow' => request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'),
                                'text-emerald-50 hover:bg-emerald-900' => ! request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'),
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endcan
                @endforeach
            </nav>
        </aside>

        <main class="flex-1">
            <header class="border-b bg-white px-6 py-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Beranda / {{ $title ?? 'Dashboard' }}</p>
                        <h1 class="text-2xl font-semibold text-emerald-950">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg border border-emerald-900 px-4 py-2 text-sm font-semibold text-emerald-950 hover:bg-emerald-50">
                            Keluar
                        </button>
                    </form>
                </div>
            </header>

            <div class="p-6">
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
