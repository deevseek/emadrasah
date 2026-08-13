<?php

return [
    'dashboard' => ['category' => 'UMUM', 'label' => 'Dashboard', 'permissions' => ['dashboard.view' => 'Melihat dashboard']],
    'school-profile' => ['category' => 'UMUM', 'label' => 'Profil Madrasah', 'permissions' => [
        'school-profile.view' => 'Melihat profil madrasah', 'school-profile.update' => 'Mengubah profil madrasah',
        'school-profile.update-logo' => 'Mengubah logo madrasah', 'school-profile.update-leader' => 'Mengubah data kepala madrasah',
    ]],
    'academic-periods' => [
        'category' => 'UMUM',
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
    'personnel' => ['category' => 'DATA MASTER', 'label'=>'Data Personalia','description'=>'Kelola data guru dan tenaga kependidikan.','permissions'=>[
        'personnel.view'=>'Melihat data personalia','personnel.create'=>'Menambahkan personalia','personnel.update'=>'Mengubah data personalia','personnel.activate'=>'Mengaktifkan dan menonaktifkan personalia','personnel.manage-account'=>'Menghubungkan akun aplikasi','personnel.view-sensitive'=>'Melihat data pribadi dan rekening','personnel.import'=>'Mengimpor data personalia','personnel.export'=>'Mengekspor data dan mengunduh template',
    ]],
    'students' => ['category' => 'DATA MASTER', 'label'=>'Data Siswa','description'=>'Kelola identitas, orang tua, dan status siswa.','permissions'=>[
        'students.view'=>'Melihat data siswa','students.create'=>'Menambahkan siswa','students.update'=>'Mengubah data siswa','students.change-status'=>'Mengubah status siswa','students.view-sensitive'=>'Melihat data pribadi siswa','students.import'=>'Mengimpor data siswa','students.export'=>'Mengekspor data dan mengunduh template',
    ]],
    'classrooms' => ['category' => 'DATA MASTER', 'label'=>'Kelas & Rombel','description'=>'Kelola rombongan belajar, wali kelas, dan penempatan siswa.','permissions'=>[
        'classrooms.view'=>'Melihat seluruh kelas dan rombel','classrooms.view-own'=>'Melihat rombel yang menjadi tanggung jawab sendiri','classrooms.create'=>'Menambahkan rombongan belajar','classrooms.update'=>'Mengubah rombongan belajar','classrooms.activate'=>'Mengaktifkan dan menonaktifkan rombel','classrooms.assign-homeroom'=>'Menentukan wali kelas','classrooms.manage-students'=>'Mengelola anggota rombel','classrooms.map-legacy'=>'Memetakan data kelas lama','classrooms.copy-structure'=>'Menyalin struktur rombel','classrooms.promote'=>'Memproses kenaikan kelas',
    ]],
    'users' => ['category' => 'SISTEM', 'label' => 'Pengguna', 'permissions' => [
        'users.view' => 'Melihat pengguna', 'users.create' => 'Menambah pengguna',
        'users.update' => 'Mengubah pengguna', 'users.activate' => 'Mengaktifkan dan menonaktifkan akun',
        'users.reset-password' => 'Mengatur ulang password', 'users.assign-role' => 'Menentukan role pengguna',
    ]],
    'roles' => ['category' => 'SISTEM', 'label' => 'Role & Hak Akses', 'permissions' => [
        'roles.view' => 'Melihat role', 'roles.create' => 'Menambah role', 'roles.update' => 'Mengubah role',
        'roles.delete' => 'Menghapus role', 'roles.manage-permissions' => 'Mengatur hak akses role',
    ]],
    'application-settings' => ['category' => 'SISTEM', 'label' => 'Pengaturan Aplikasi', 'permissions' => [
        'application-settings.view' => 'Melihat pengaturan aplikasi', 'application-settings.update' => 'Mengubah pengaturan aplikasi',
    ]],
    'rfid-cards' => ['category'=>'DATA MASTER','label'=>'Kartu RFID Siswa','permissions'=>['rfid-cards.manage'=>'Mendaftarkan, mengganti, dan menonaktifkan kartu RFID siswa']],
    'academic-attendance'=>['category'=>'AKADEMIK','label'=>'Absensi Siswa','permissions'=>['academic-attendance.view'=>'Melihat absensi siswa','academic-attendance.manage'=>'Mengelola absensi siswa']],
    'academic-grades'=>['category'=>'AKADEMIK','label'=>'Daftar Nilai','permissions'=>['academic-grades.view'=>'Melihat daftar nilai','academic-grades.manage'=>'Mengelola daftar nilai']],
    'academic-subjects'=>['category'=>'AKADEMIK','label'=>'Mata Pelajaran','permissions'=>['academic-subjects.view'=>'Melihat mata pelajaran','academic-subjects.manage'=>'Mengelola mata pelajaran']],
    'teaching-journals'=>['category'=>'AKADEMIK','label'=>'Jurnal Mengajar','permissions'=>['teaching-journals.view'=>'Melihat jurnal mengajar','teaching-journals.manage'=>'Mengelola jurnal mengajar','teaching-journals.view-all'=>'Melihat semua jurnal mengajar']],
    'classroom-journals'=>['category'=>'AKADEMIK','label'=>'Jurnal Kelas','permissions'=>['classroom-journals.view'=>'Melihat jurnal kelas','classroom-journals.manage'=>'Mengelola jurnal kelas','classroom-journals.view-all'=>'Melihat semua jurnal kelas']],
    'academic-reports'=>['category'=>'AKADEMIK','label'=>'Laporan Akademik','permissions'=>['academic-reports.view'=>'Melihat laporan akademik','academic-reports.export'=>'Mencetak atau mengekspor laporan akademik']],
];
