# Portal Orang Tua

Portal modular monolith tersedia pada `/parent`. `GuardianProfile` mengikat satu akun pengguna, sedangkan `student_guardians` membentuk relasi many-to-many dan menyimpan kapabilitas per anak. Semua resource anak wajib diperoleh melalui `GuardianAccessService`, bukan mempercayai ID URL. Dashboard memakai absensi siswa yang sudah ada dan menghitung **Jadwal Saat Ini** dalam zona Asia/Jakarta.

Permission: `parent.dashboard.view`, `parent.children.view`, `parent.schedule.view`, `parent.attendance.view`, `parent.leave.create`, `parent.leave.view`, dan `parent.finance.view`. Izin memiliki riwayat transisi immutable; lampiran disimpan sebagai path, bukan base64.
