# Arsitektur E-Madrasah MI Muslimat NU

E-Madrasah dibangun sebagai aplikasi Laravel 12 server-rendered dengan Blade, Vite, Tailwind CSS 4, database MySQL/MariaDB, queue database, scheduler Laravel, dan otorisasi berbasis permission. Backend memakai controller tipis, Form Request, Policy, Service/Action untuk proses bisnis kritis, enum untuk status, audit log, dan transaksi database untuk proses keuangan serta perubahan akademik yang berisiko.

## Lapisan Aplikasi

1. **Presentation**: Blade layout backoffice dan portal wali, komponen form/tabel konsisten, validasi error dekat field.
2. **HTTP**: controller hanya menerima request, memanggil service, dan mengembalikan response.
3. **Authorization**: middleware permission dan policy per domain; menu hanya lapisan UI, bukan kontrol keamanan utama.
4. **Domain services**: logika seperti aktivasi tahun ajaran, aktivasi semester, pembayaran, absensi, jadwal, dan approval berada di service/action.
5. **Persistence**: Eloquent relationship eksplisit, index pencarian, foreign key, unique constraint, decimal untuk uang.
6. **Background jobs**: queue database untuk notifikasi, export besar, backup, dan pekerjaan berat.
7. **Audit**: tabel `activity_logs` dan `login_histories` menyimpan aktivitas aman tanpa password, token, API key, isi file, atau detail gaji lengkap.

## Fondasi Fase 1

Fase fondasi menyediakan autentikasi tanpa registrasi publik, status akun aktif/nonaktif, riwayat login, role-permission internal, profil madrasah, tahun ajaran, semester, pengaturan sistem terstruktur, layout dashboard, dan seeder aman berbasis environment.

## Keamanan

- Password menggunakan hashing Laravel.
- Login diberi rate limiting.
- Session diregenerasi saat login.
- Route backoffice memakai `auth`, `active`, dan permission middleware.
- Output Blade di-escape secara default.
- File privat direncanakan memakai signed route dan metadata database.
- Secrets tidak disimpan di repository.
