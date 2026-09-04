<?php

declare(strict_types=1);

return [
    'poll_interval' => (int) env('NOTIFICATION_POLL_INTERVAL', 10),
    'modules' => [
        'akses' => ['label' => 'Akun & Akses', 'permissions' => ['users.view', 'roles.view'], 'route' => 'users.index'],
        'application-settings' => ['label' => 'Pengaturan', 'permissions' => ['application-settings.view'], 'route' => 'application-settings.edit'],
        'school-profile' => ['label' => 'Profil Madrasah', 'permissions' => ['school-profile.view'], 'route' => 'school-profile.show'],
        'periode-akademik' => ['label' => 'Tahun Ajaran', 'permissions' => ['academic-periods.view'], 'route' => 'academic-periods.index'],
        'personnel' => ['label' => 'Personalia', 'permissions' => ['personnel.view'], 'route' => 'personnel.index'],
        'students' => ['label' => 'Kesiswaan', 'permissions' => ['students.view'], 'route' => 'students.index'],
        'kelas-rombel' => ['label' => 'Kelas & Rombel', 'permissions' => ['classrooms.view', 'classrooms.view-own'], 'route' => 'classrooms.index'],
        'akademik' => ['label' => 'Akademik', 'permissions' => ['academic-attendance.view', 'academic-grades.view', 'teaching-journals.view'], 'route' => 'academic.attendance.index'],
        'izin-siswa' => ['label' => 'Izin Siswa', 'permissions' => ['parent.attendance.view', 'academic-attendance.view'], 'route' => 'parent.attendance'],
        'hrd' => ['label' => 'HRD', 'permissions' => ['hrd.dashboard.view', 'personnel-attendance.view-all'], 'route' => 'hrd.dashboard'],
        'payroll' => ['label' => 'Payroll', 'permissions' => ['personnel-payroll.view'], 'route' => 'hrd.payroll.index'],
        'finance' => ['label' => 'Keuangan', 'permissions' => ['finance.dashboard.view', 'finance.payment.view'], 'route' => 'finance.dashboard'],
        'bri' => ['label' => 'Integrasi BRI', 'permissions' => ['finance.bri.reconcile'], 'route' => 'finance.bri.reconciliation'],
        'bri-callback' => ['label' => 'Integrasi BRI', 'permissions' => ['finance.bri.reconcile'], 'route' => 'finance.bri.reconciliation'],
        'bri-connection' => ['label' => 'Integrasi BRI', 'permissions' => ['finance.bri.configure'], 'route' => 'application-settings.edit'],
        'bri-reconciliation' => ['label' => 'Rekonsiliasi BRI', 'permissions' => ['finance.bri.reconcile'], 'route' => 'finance.bri.reconciliation'],
        'bri-settings' => ['label' => 'Integrasi BRI', 'permissions' => ['finance.bri.configure'], 'route' => 'application-settings.edit'],
        'website' => ['label' => 'Website', 'permissions' => ['website.dashboard.view'], 'route' => 'website.index'],
        'rfid-card' => ['label' => 'RFID Siswa', 'permissions' => ['students.view'], 'route' => 'students.index'],
        'rfid-device' => ['label' => 'Perangkat RFID', 'permissions' => ['rfid-device.manage'], 'route' => 'rfid-devices.index'],
    ],
];
