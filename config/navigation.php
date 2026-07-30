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
        'label' => 'Akun & Akses',
        'items' => [
            ['label' => 'Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'users.view', 'icon' => 'users'],
            ['label' => 'Role & Hak Akses', 'route' => 'roles.index', 'active' => 'roles.*', 'permission' => 'roles.view', 'icon' => 'roles'],
        ],
    ],
];
