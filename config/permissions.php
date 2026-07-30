<?php

return [
    'dashboard' => ['label' => 'Dashboard', 'permissions' => ['dashboard.view' => 'Melihat dashboard']],
    'school-profile' => ['label' => 'Profil Madrasah', 'permissions' => [
        'school-profile.view' => 'Melihat profil madrasah', 'school-profile.update' => 'Mengubah profil madrasah',
        'school-profile.update-logo' => 'Mengubah logo madrasah', 'school-profile.update-leader' => 'Mengubah data kepala madrasah',
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
