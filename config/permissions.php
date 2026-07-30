<?php

return [
    'dashboard' => ['label' => 'Dashboard', 'permissions' => ['dashboard.view' => 'Melihat dashboard']],
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
