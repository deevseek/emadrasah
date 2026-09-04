# Portal Orang Tua

Portal modular monolith tersedia pada `/parent`. Halaman masuk khusus orang tua tersedia pada `/parent/login` dan hanya menerima akun dengan role `orang-tua`. Tautan menuju halaman tersebut tersedia pada navigasi landing page melalui pengaturan `parent_portal_label` dan `parent_portal_url` yang diisi oleh `LandingPageSeeder`.

Orang tua dapat mendaftar mandiri melalui `/parent/register`. Sistem mencocokkan NISN dan tanggal lahir dengan siswa aktif secara server-side, lalu dalam satu transaction membuat akun ber-role `orang-tua`, profil wali, dan relasi anak. Password tidak pernah dikirim melalui email. Setelah transaction berhasil, email konfirmasi pendaftaran diproses melalui queue.

`GuardianProfile` mengikat satu akun pengguna, sedangkan `student_guardians` membentuk relasi many-to-many dan menyimpan kapabilitas per anak. Semua resource anak wajib diperoleh melalui `GuardianAccessService`, bukan mempercayai ID URL. Dashboard memakai absensi siswa yang sudah ada dan menghitung **Jadwal Saat Ini** dalam zona Asia/Jakarta.

Permission: `parent.dashboard.view`, `parent.children.view`, `parent.schedule.view`, `parent.attendance.view`, `parent.leave.create`, `parent.leave.view`, dan `parent.finance.view`. Izin memiliki riwayat transisi immutable; lampiran disimpan sebagai path, bukan base64.

## Email aplikasi

Atur `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, dan `MAIL_FROM_NAME` pada `.env` produksi. Jangan commit kredensial SMTP. Karena email konfirmasi pendaftaran menggunakan queue dan koneksi default aplikasi adalah `database`, jalankan worker secara permanen, misalnya `php artisan queue:work --tries=3`. Tanda terima pembayaran SPP otomatis dikirim ke semua wali aktif yang memiliki akses keuangan untuk anak tersebut, serta dilampiri PDF resmi.
