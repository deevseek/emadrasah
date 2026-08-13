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
            ['label' => 'Data Personalia', 'route' => 'personnel.index', 'active' => 'personnel.*', 'permission' => 'personnel.view', 'icon' => 'personnel'],
        ],
    ],
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
];
