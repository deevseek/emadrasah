# Permission Matrix

Permission memakai format `domain.action` dan diperiksa oleh middleware/policy, bukan nama role langsung.

## Role Awal

- Super Admin: seluruh permission.
- Admin Madrasah: administrasi operasional mayoritas.
- Kepala Madrasah: dashboard, approval, laporan, audit terbatas.
- Bendahara: tagihan, pembayaran, keuangan, payroll.
- Tata Usaha: data master, surat, arsip.
- Operator: input akademik dan laporan operasional.
- Guru Kelas: kelas, siswa kelas, absensi siswa, jurnal, nilai.
- Guru Mata Pelajaran: jadwal, jurnal, nilai mapel.
- Guru BTAQ atau Murobi: BTAQ, hafalan, jurnal BTAQ.
- Guru Full Day: absensi dan jurnal sesuai penugasan.
- Wali Murid: portal wali untuk anak terkait.

## Permission Fondasi

- `dashboard.view`
- `school-profile.view`, `school-profile.update`
- `academic-years.view`, `academic-years.create`, `academic-years.update`, `academic-years.activate`
- `semesters.view`, `semesters.create`, `semesters.update`, `semesters.activate`
- `settings.view`, `settings.update`
- `users.view`, `users.create`, `users.update`, `users.deactivate`
- `roles.view`, `roles.update`
- `audit.view`
- `backup.view`, `backup.run`
