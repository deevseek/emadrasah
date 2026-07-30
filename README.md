# e-Madrasah — Fondasi Bersih

Repository ini adalah fondasi bersih aplikasi e-Madrasah berbasis Laravel 12. Modul bisnis lama telah dihapus agar pengembangan berikutnya dapat dilakukan dengan alur yang lebih sederhana, modular, dan teruji tanpa mengubah shell antarmuka backoffice.

## Fitur yang tersedia

- Login dan logout berbasis session dengan pembatasan percobaan login.
- Lupa password, reset password, dan ganti password.
- Status akun aktif/nonaktif.
- Satu administrator development dari seeder.
- Role `super-admin` dan permission dasar `dashboard.view` melalui Spatie Laravel Permission.
- Infrastruktur Spatie Activitylog.
- UI backoffice Blade yang responsif: sidebar, header, breadcrumb, flash message, form, card, table, dan komponen generik.
- Dashboard fondasi tanpa query atau metrik modul bisnis.
- Tailwind CSS 4, Vite, dan Livewire 3 sebagai fondasi frontend.

## Instalasi development

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Sesuaikan koneksi database di `.env`. Kredensial development bawaan adalah `admin@example.test` dengan password `password` hanya jika `SEED_ADMIN_PASSWORD` kosong dan aplikasi tidak berjalan di production.

## Konfigurasi identitas

Identitas visual minimal tidak disimpan di database. Atur `MADRASAH_NAME`, `MADRASAH_APP_NAME`, dan opsional `MADRASAH_LOGO` pada environment. Navigasi fondasi berada di `config/navigation.php`.

Laporan reset lengkap tersedia di [`docs/rebuild-reset-report.md`](docs/rebuild-reset-report.md).
