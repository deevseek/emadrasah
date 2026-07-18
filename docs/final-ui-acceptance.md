# FINAL UI ACCEPTANCE — E-MADRASAH EXISTING MODULES

Dokumen ini mencatat inventaris route UI GET yang diaudit pada modul existing branch saat ini. Karena environment tidak dapat menyelesaikan instalasi Composer dari GitHub, status HTTP/browser di bawah adalah target verifikasi yang dikunci oleh feature/browser test dan perlu dijalankan ulang pada CI atau mesin dengan dependency lengkap.

| Modul | Route | Role | Status HTTP | UI | Responsive | Console | Hasil |
|--------|-------|------|-------------|----|------------|---------|-------|
| Dashboard | `/dashboard` | Super Admin, Admin, Kepala Madrasah, Bendahara, Operator, Tata Usaha, Guru | 200 | Header Beranda, sidebar aktif, metrik nyata | Desktop/mobile | Bersih | Siap verifikasi |
| Profil Madrasah | `/school-profile` | Super Admin, Admin, Operator | 200 | Form profil nyata | Desktop/mobile | Bersih | Siap verifikasi |
| Tahun Ajaran | `/academic-years` | Super Admin, Admin, Operator | 200 | Tabel dan aksi CRUD | Desktop/mobile | Bersih | Siap verifikasi |
| Semester | `/semesters` | Super Admin, Admin, Operator | 200 | Tabel dan aksi CRUD | Desktop/mobile | Bersih | Siap verifikasi |
| Guru dan Pegawai | `/employees` | Super Admin, Admin, Operator, Tata Usaha, Kepala Madrasah | 200 | Tabel dan aksi CRUD | Desktop/mobile | Bersih | Siap verifikasi |
| Siswa | `/students` | Super Admin, Admin, Operator, Tata Usaha, Guru terkait | 200 | Tabel dan aksi CRUD | Desktop/mobile | Bersih | Siap verifikasi |
| Orang Tua/Wali | `/guardians` | Super Admin, Admin, Operator, Tata Usaha, Guru terkait | 200 | Tabel dan aksi CRUD | Desktop/mobile | Bersih | Siap verifikasi |
| Kelas dan Rombel | `/academic/classrooms` | Super Admin, Admin, Operator, Kepala Madrasah, Guru | 200 | Tabel dan aksi CRUD | Desktop/mobile | Bersih | Siap verifikasi |
| Mata Pelajaran | `/academic/subjects` | Super Admin, Admin, Operator, Guru | 200 | Title Indonesia, filter, export, tabel profesional, badge status, aksi berjarak | Desktop/mobile | Bersih | Diperbaiki/siap verifikasi |
| Penugasan Mengajar | `/academic/teaching-assignments` | Super Admin, Admin, Operator, Guru terkait | 200 | Tabel dan filter | Desktop/mobile | Bersih | Siap verifikasi |
| Jadwal Pelajaran | `/academic/schedules` | Super Admin, Admin, Operator, Guru terkait | 200 | Tabel dan filter | Desktop/mobile | Bersih | Siap verifikasi |
| Jurnal Mengajar | `/teaching-journals` | Super Admin, Admin, Guru | 200 | Jadwal hari ini, filter, export, table component | Desktop/mobile | Bersih | Siap verifikasi |
| Absensi Pegawai | `/employee-attendances` | Super Admin, Admin, Kepala Madrasah, Tata Usaha, Operator | 200 | Tabel kehadiran | Desktop/mobile | Bersih | Siap verifikasi |
| Absensi Siswa | `/student-attendances/create` | Super Admin, Admin, Guru Kelas sah | 200 | Form nyata, table component, action standar | Desktop/mobile | Bersih | Diperbaiki/siap verifikasi |
| Penilaian dan Rapor | `/penilaian-rapor/konfigurasi` | Super Admin, Admin, Operator | 200 | Konfigurasi dengan CTA valid | Desktop/mobile | Bersih | Siap verifikasi |
| BTAQ | `/btaq`, `/btaq-sessions`, `/btaq-groups`, `/btaq-reports` | Super Admin, Admin, Guru BTAQ | 200 | Menu hanya jika permission tersedia | Desktop/mobile | Bersih | Siap verifikasi |
| Keuangan Siswa | `/student-finance/fee-types` | Super Admin, Admin, Bendahara | 200 | Title Indonesia, filter nyata, Rupiah, enum diterjemahkan, aksi berjarak | Desktop/mobile | Bersih | Siap verifikasi |
| Keuangan Operasional | `/operational-finance` | Super Admin, Admin, Bendahara | 200 | Menu hanya route tersedia | Desktop/mobile | Bersih | Siap verifikasi |
| Payroll Pegawai | `/payroll-pegawai/components` | Super Admin, Admin, Bendahara | 200 | Enum diterjemahkan, Rupiah, aksi standar | Desktop/mobile | Bersih | Siap verifikasi |
| Payroll Pegawai | `/payroll-pegawai/salary-profiles` | Super Admin, Admin, Bendahara | 200 | CTA utama/sekunder, rekening tidak penuh pada index, tabel rapi | Desktop/mobile | Bersih | Siap verifikasi |
| Payroll Pegawai | `/payroll-pegawai/periods` | Super Admin, Admin, Bendahara | 200 | Cutoff, tanggal pembayaran, status badge | Desktop/mobile | Bersih | Siap verifikasi |
| Payroll Pegawai | `/payroll-pegawai/reports` | Super Admin, Admin, Bendahara | 200 | Tombol export/print standar, cards, empty state | Desktop/mobile | Bersih | Siap verifikasi |

## Matrix Role dan Permission

| Role | Menu | Route | Expected | Actual |
|------|------|-------|----------|--------|
| Super Admin | Seluruh menu administratif existing | Seluruh route prioritas | 200 | Dikunci oleh seeder dan test |
| Admin | Akademik, absensi, penilaian, keuangan sesuai permission | Route prioritas | 200 | Dikunci oleh seeder |
| Kepala Madrasah | Monitoring akademik/kehadiran | Route read-only | 200/403 sesuai permission | Dikunci oleh seeder |
| Bendahara | Keuangan Siswa, Keuangan Operasional, Payroll Pegawai | Route keuangan | 200 | Dikunci oleh seeder dan test |
| Operator | Data madrasah, akademik, penilaian terbatas | Route operasional | 200/403 sesuai permission | Dikunci oleh seeder |
| Tata Usaha | Data siswa/pegawai dan transaksi terbatas | Route TU | 200/403 sesuai permission | Dikunci oleh seeder |
| Guru Kelas | Akademik kelas, Absensi Siswa kelas sendiri | `/student-attendances/create` kelas sah | 200 | Permission create ditambahkan; scope kelas tetap melalui service |
| Guru Mata Pelajaran | Akademik/Jurnal miliknya | Jurnal dan jadwal sendiri | 200/403 sesuai scope | Dikunci oleh seeder |
| Guru BTAQ | BTAQ terkait | Route BTAQ terkait | 200/403 sesuai scope | Dikunci oleh seeder |
| Pegawai biasa | Slip Gaji Saya bila berhak | `/payroll-pegawai/payslips/mine` | 200 bila berizin | Dikunci oleh sidebar permission |
