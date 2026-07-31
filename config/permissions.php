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
    'students' => ['label'=>'Data Siswa','description'=>'Kelola identitas, orang tua, dan status siswa.','permissions'=>[
        'students.view'=>'Melihat data siswa','students.create'=>'Menambahkan siswa','students.update'=>'Mengubah data siswa','students.change-status'=>'Mengubah status siswa','students.view-sensitive'=>'Melihat data pribadi siswa','students.import'=>'Mengimpor data siswa','students.export'=>'Mengekspor data dan mengunduh template',
    ]],
    'classrooms' => ['label'=>'Kelas & Rombel','description'=>'Kelola rombongan belajar, wali kelas, dan penempatan siswa.','permissions'=>[
        'classrooms.view'=>'Melihat seluruh kelas dan rombel','classrooms.view-own'=>'Melihat rombel yang menjadi tanggung jawab sendiri','classrooms.create'=>'Menambahkan rombongan belajar','classrooms.update'=>'Mengubah rombongan belajar','classrooms.activate'=>'Mengaktifkan dan menonaktifkan rombel','classrooms.assign-homeroom'=>'Menentukan wali kelas','classrooms.manage-students'=>'Mengelola anggota rombel','classrooms.map-legacy'=>'Memetakan data kelas lama','classrooms.copy-structure'=>'Menyalin struktur rombel','classrooms.promote'=>'Memproses kenaikan kelas',
    ]],
    'subjects' => ['label'=>'Mata Pelajaran & Struktur JP','description'=>'Kelola master mata pelajaran dan beban JP per tingkat.','permissions'=>[
        'subjects.view'=>'Melihat daftar mata pelajaran','subjects.create'=>'Menambahkan mata pelajaran','subjects.update'=>'Mengubah mata pelajaran','subjects.view-loads'=>'Melihat matriks struktur JP','subjects.manage-loads'=>'Mengubah matriks struktur JP','subjects.activate'=>'Mengaktifkan atau menonaktifkan mata pelajaran','subjects.export'=>'Mengekspor Mata Pelajaran dan Struktur JP',
    ]],
    'teaching-assignments' => ['label'=>'Pembagian Tugas Mengajar','description'=>'Pratinjau dan pencocokan workbook pembagian tugas.','permissions'=>[
        'teaching-assignments.import'=>'Mengimpor dan memeriksa pratinjau pembagian tugas XLSX','teaching-assignments.view'=>'Melihat pembagian tugas dan rekap JP','teaching-assignments.create'=>'Membuat draft dan penugasan','teaching-assignments.update'=>'Mengubah penugasan draft','teaching-assignments.manage-duties'=>'Mengelola tugas tambahan','teaching-assignments.activate'=>'Mengaktifkan pembagian tugas','teaching-assignments.export'=>'Mengekspor pembagian tugas','teaching-assignments.print'=>'Mencetak pembagian tugas','teaching-assignments.view-own'=>'Melihat tugas mengajar sendiri','teaching-assignments.print-own'=>'Mencetak tugas mengajar sendiri',
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
