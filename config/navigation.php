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
        'label' => 'Akademik',
        'items' => [
            ['label' => 'Mata Pelajaran & JP', 'route' => 'subjects.index', 'active' => 'subjects.*', 'permission' => 'subjects.view', 'icon' => 'calendar'],
            ['label' => 'Pembagian Tugas Mengajar', 'route' => 'teaching-assignments.index', 'active' => 'teaching-assignments.*', 'permission' => 'teaching-assignments.view', 'icon' => 'calendar'],
            ['label' => 'Tugas Mengajar Saya', 'route' => 'teaching-assignments.my', 'active' => 'teaching-assignments.my', 'permission' => 'teaching-assignments.view-own', 'icon' => 'calendar'],
        ],
    ],
    [
        'label' => 'Akun & Akses',
        'items' => [
            ['label' => 'Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'users.view', 'icon' => 'users'],
            ['label' => 'Role & Hak Akses', 'route' => 'roles.index', 'active' => 'roles.*', 'permission' => 'roles.view', 'icon' => 'roles'],
        ],
    ],
];
