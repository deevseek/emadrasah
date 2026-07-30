<?php

return [
    'dashboard' => ['label' => 'Dashboard', 'permissions' => ['dashboard.view' => 'Melihat dashboard']],
    'school-profile' => ['label' => 'Profil Madrasah', 'permissions' => [
        'school-profile.view' => 'Melihat profil madrasah', 'school-profile.update' => 'Mengubah profil madrasah',
        'school-profile.update-logo' => 'Mengubah logo madrasah', 'school-profile.update-leader' => 'Mengubah data kepala madrasah',
    ]],
    'academic-periods' => [
        'label' => 'Tahun Ajaran & Semester',
        'description' => 'Atur periode akademik yang digunakan madrasah.',
        'permissions' => [
            'academic-periods.view' => 'Melihat tahun ajaran dan semester',
            'academic-periods.create' => 'Menambahkan tahun ajaran',
            'academic-periods.update' => 'Mengubah tahun ajaran dan semester',
            'academic-periods.activate' => 'Menentukan periode aktif',
            'academic-periods.delete' => 'Menghapus tahun ajaran',
        ],
    ],
    'personnel' => ['label'=>'Data Personalia','description'=>'Kelola data guru dan tenaga kependidikan.','permissions'=>[
        'personnel.view'=>'Melihat data personalia','personnel.create'=>'Menambahkan personalia','personnel.update'=>'Mengubah data personalia','personnel.activate'=>'Mengaktifkan dan menonaktifkan personalia','personnel.manage-account'=>'Menghubungkan akun aplikasi','personnel.view-sensitive'=>'Melihat data pribadi dan rekening','personnel.import'=>'Mengimpor data personalia','personnel.export'=>'Mengekspor data dan mengunduh template',
    ]],
    'users' => ['label' => 'Pengguna', 'permissions' => [
        'users.view' => 'Melihat pengguna', 'users.create' => 'Menambah pengguna',
        'users.update' => 'Mengubah pengguna', 'users.activate' => 'Mengaktifkan dan menonaktifkan akun',
        'users.reset-password' => 'Mengatur ulang password', 'users.assign-role' => 'Menentukan role pengguna',
    ]],
    'roles' => ['label' => 'Role & Hak Akses', 'permissions' => [
        'roles.view' => 'Melihat role', 'roles.create' => 'Menambah role', 'roles.update' => 'Mengubah role',
        'roles.delete' => 'Menghapus role', 'roles.manage-permissions' => 'Mengatur hak akses role',
    ]],
];
