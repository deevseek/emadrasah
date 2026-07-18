# E-Madrasah MI Muslimat NU

E-Madrasah MI Muslimat NU adalah Sistem Informasi Manajemen Madrasah berbasis Laravel untuk fondasi akademik, kepegawaian, absensi, tagihan, pembayaran, laporan, portal wali, audit, dan backup.

## Teknologi

- PHP 8.2 atau lebih baru.
- Laravel 12.
- MySQL 8 atau MariaDB kompatibel dengan charset `utf8mb4`.
- Blade, Tailwind CSS 4, Vite, Alpine.js-ready.
- Queue, cache, dan session berbasis database.
- PHPUnit dan Laravel Pint untuk QA.

## Instalasi Development

```bash
git clone https://github.com/deevseek/emadrasah.git
cd emadrasah
cp .env.example .env
composer install
npm install
php artisan key:generate
```

Buat database MySQL:

```sql
CREATE DATABASE emadrasah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
php artisan storage:link
```

Jalankan server, queue, dan Vite:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Scheduler development dapat diuji dengan:

```bash
php artisan schedule:run
```

## Akun Development

Seeder membaca kredensial dari environment:

```env
SEED_ADMIN_NAME="Administrator"
SEED_ADMIN_EMAIL=admin@example.test
SEED_ADMIN_PASSWORD=
```

Jika `SEED_ADMIN_PASSWORD` kosong, development lokal memakai `password`. Jangan gunakan nilai default ini di production.

## Pengujian dan Build

```bash
php artisan test
./vendor/bin/pint --test
npm run build
```

## Modul Fondasi Saat Ini

- Login/logout tanpa registrasi publik.
- Status akun aktif/nonaktif.
- Riwayat login dengan IP dan user agent.
- Role dan permission internal.
- Profil madrasah.
- Tahun ajaran dan semester.
- Pengaturan sistem terstruktur.
- Dashboard awal berbasis data database.
- Dokumentasi arsitektur, permission, database design, roadmap, dan deployment.

## Kebijakan Keamanan

Secrets, `.env`, backup, dan file pengguna tidak boleh masuk Git. Semua route backoffice wajib memakai autentikasi, status akun aktif, dan permission/policy. Audit log tidak boleh menyimpan password, token, API key, isi file, atau detail gaji lengkap.

## Deployment

Lihat `docs/deployment.md` untuk panduan Ubuntu, Nginx, PHP-FPM, MySQL, Supervisor queue, scheduler cron, permission storage, dan prosedur backup/restore manual.

### Modul Inventaris dan Aset
Dokumentasi modul tersedia di `docs/modules/inventory-assets.md`.
