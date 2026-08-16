<?php

return [
    [
        'label' => 'Beranda',
        'items' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'active' => 'dashboard',
                'permission' => 'dashboard.view',
                'icon' => 'dashboard',
            ],
        ],
    ],
    [
        'label' => 'Data Madrasah',
        'items' => [
            ['label' => 'Profil Madrasah', 'route' => 'school-profile.show', 'active' => 'school-profile.*', 'permission' => 'school-profile.view', 'icon' => 'school'],
            ['label' => 'Tahun Ajaran & Semester', 'route' => 'academic-periods.index', 'active' => 'academic-periods.*', 'permission' => 'academic-periods.view', 'icon' => 'calendar'],
        ],
    ],
    ['label'=>'HRD / Kepegawaian','items'=>[
        ['label'=>'Dashboard HRD','route'=>'hrd.dashboard','active'=>'hrd.dashboard','permission'=>'hrd.dashboard.view','icon'=>'dashboard'],
        ['label'=>'Data Personalia','route'=>'personnel.index','active'=>'personnel.*','permission'=>'personnel.view','icon'=>'personnel'],
        ['label'=>'Absensi Pegawai','route'=>'hrd.attendance.index','active'=>'hrd.attendance.index','permission'=>'personnel-attendance.view-all','icon'=>'calendar'],
        ['label'=>'Absensi Saya','route'=>'hrd.attendance.mine','active'=>'hrd.attendance.mine','permission'=>'personnel-attendance.check','icon'=>'calendar'],
        ['label'=>'Izin & Cuti','route'=>'hrd.leave.index','active'=>'hrd.leave.*','permission'=>'personnel-leave.view','icon'=>'document'],
        ['label'=>'Payroll & Gaji','route'=>'hrd.payroll.index','active'=>'hrd.payroll.*','permission_any'=>['personnel-payroll.view','personnel-payroll.view-own'],'icon'=>'document'],
        ['label'=>'Kasbon Pegawai','route'=>'hrd.cash-advance.index','active'=>'hrd.cash-advance.*','permission_any'=>['personnel-cash-advance.view','personnel-cash-advance.view-own'],'icon'=>'document'],
        ['label'=>'Laporan HRD','route'=>'hrd.reports.index','active'=>'hrd.reports.*','permission'=>'personnel-attendance.report','icon'=>'document'],
        ['label'=>'Pengaturan HRD','route'=>'application-settings.edit','active'=>'application-settings.*','permission'=>'hrd-settings.view','icon'=>'filter'],
    ]],
    [
        'label' => 'Kesiswaan',
        'items' => [
            ['label' => 'Data Siswa', 'route' => 'students.index', 'active' => 'students.*', 'permission' => 'students.view', 'icon' => 'students'],
            ['label' => 'Kelas & Rombel', 'route' => 'classrooms.index', 'active' => 'classrooms.*', 'permission_any' => ['classrooms.view', 'classrooms.view-own'], 'icon' => 'classrooms'],
        ],
    ],
    [
        'label' => 'Akun & Akses',
        'items' => [
            ['label' => 'Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'users.view', 'icon' => 'users'],
            ['label' => 'Role & Hak Akses', 'route' => 'roles.index', 'active' => 'roles.*', 'permission' => 'roles.view', 'icon' => 'roles'],
            ['label' => 'Pengaturan Aplikasi', 'route' => 'application-settings.edit', 'active' => 'application-settings.*', 'permission' => 'application-settings.view', 'icon' => 'filter'],
        ],
    ],
    ['label'=>'Akademik','items'=>[
        ['label'=>'Absensi Siswa','route'=>'academic.attendance.index','active'=>'academic.attendance.*','permission'=>'academic-attendance.view','icon'=>'calendar'],
        ['label'=>'Mata Pelajaran','route'=>'academic.subjects.index','active'=>'academic.subjects.*','permission'=>'academic-subjects.view','icon'=>'document'],
        ['label'=>'Daftar Nilai','route'=>'academic.grades.index','active'=>'academic.grades.*','permission'=>'academic-grades.view','icon'=>'academic'],
        ['label'=>'Jurnal Mengajar','route'=>'academic.teaching-journals.index','active'=>'academic.teaching-journals.*','permission'=>'teaching-journals.view','icon'=>'academic'],
        ['label'=>'Jurnal Kelas','route'=>'academic.classroom-journals.index','active'=>'academic.classroom-journals.*','permission'=>'classroom-journals.view','icon'=>'classrooms'],
        ['label'=>'Laporan Akademik','route'=>'academic.reports.index','active'=>'academic.reports.*','permission'=>'academic-reports.view','icon'=>'document'],
    ]],
    ['label'=>'Portal Orang Tua','items'=>[
        ['label'=>'Dashboard','route'=>'parent.dashboard','active'=>'parent.dashboard','permission'=>'parent.dashboard.view','icon'=>'dashboard'],
        ['label'=>'Anak Saya','route'=>'parent.children','active'=>'parent.children','permission'=>'parent.children.view','icon'=>'students'],
        ['label'=>'Jadwal & Kegiatan','route'=>'parent.schedule','active'=>'parent.schedule','permission'=>'parent.schedule.view','icon'=>'calendar'],
        ['label'=>'Absensi & Izin','route'=>'parent.attendance','active'=>'parent.attendance','permission'=>'parent.attendance.view','icon'=>'document'],
        ['label'=>'SPP & Pembayaran','route'=>'parent.finance','active'=>'parent.finance','permission'=>'parent.finance.view','icon'=>'document'],
        ['label'=>'Profil','route'=>'parent.profile','active'=>'parent.profile','permission'=>'parent.dashboard.view','icon'=>'users'],
    ]],
    ['label'=>'Keuangan','items'=>[
        ['label'=>'Dashboard Keuangan','route'=>'finance.dashboard','active'=>'finance.dashboard','permission'=>'finance.dashboard.view','icon'=>'dashboard'],
        ['label'=>'Jenis Tagihan','route'=>'finance.fee-types.index','active'=>'finance.fee-types.*','permission'=>'finance.fee-type.manage','icon'=>'document'],
        ['label'=>'Tagihan Siswa','route'=>'finance.invoices.index','active'=>'finance.invoices.*','permission'=>'finance.invoice.view','icon'=>'document'],
        ['label'=>'Generate SPP Bulanan','route'=>'finance.spp.create','active'=>'finance.spp.*','permission'=>'finance.invoice.create','icon'=>'calendar'],
        ['label'=>'Pembayaran Siswa','route'=>'finance.payments.index','active'=>'finance.payments.*','permission'=>'finance.payment.view','icon'=>'document'],
        ['label'=>'Rekonsiliasi BRI','route'=>'finance.bri.reconciliation','active'=>'finance.bri.*','permission'=>'finance.bri.reconcile','icon'=>'filter'],
        ['label'=>'Laporan Keuangan Siswa','route'=>'finance.reports.index','active'=>'finance.reports.*','permission'=>'finance.report.view','icon'=>'document'],
        ['label'=>'Pengaturan Pembayaran','route'=>'application-settings.edit','active'=>'application-settings.*','permission'=>'finance.bri.configure','icon'=>'filter'],
    ]],
];
