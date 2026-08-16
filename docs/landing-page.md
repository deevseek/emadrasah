# Modul Landing Page MI Sultan Fatah

## Arsitektur
Modul `Website` merupakan bagian modular monolith Laravel. `LandingPageService` menyusun data publik dari pengaturan, konten berulang, `SchoolProfile`, `Student`, dan `Personnel`. Blade tidak menjalankan query. Konten berulang berada di tabel terpisah: `landing_programs`, `landing_facilities`, `landing_achievements`, `landing_news`, `landing_testimonials`, `landing_highlights`, dan `landing_statistics`; konfigurasi sederhana memakai `landing_page_settings`.

## Route
Publik: `GET /`, `GET /berita`, dan `GET /berita/{slug}`. Admin: `GET /website`, `/website/pengaturan`, `/website/preview`, serta CRUD `/website/konten/{type}`. Seluruh route admin memakai middleware `auth`, `active`, `force-password-change`, dan permission.

## Permission
`website.dashboard.view`, `website.settings.view`, `website.settings.update`, dan `website.content.manage`. Migration hanya memberikannya kepada Super Admin; operator dapat diberi akses melalui pengelolaan role.

## Media
JPG/JPEG/PNG/WEBP maksimal 4 MB disimpan oleh disk `public` di `landing-page/{bagian}` dengan nama acak Laravel. `MediaService` menghapus aset lama hanya bila berada di direktori landing page. Jalankan `php artisan storage:link` pada deployment.

## Statistik dan profil
Identitas/logo/kontak bersumber dari `SchoolProfile`. Sumber statistik otomatis: siswa aktif dari `Student`, pegawai aktif dari `Personnel`, program aktif, atau prestasi aktif. Nilai manual tersedia di `landing_statistics`; tidak ada angka produksi palsu.

## SEO dan publikasi
Tab SEO mengelola title, description, keyword, dan OpenGraph. Berita menggunakan title/excerpt sendiri, canonical URL, serta hanya status `published` dengan waktu terbit yang sudah lewat. `landing_enabled=0` menampilkan halaman pemeliharaan publik yang aman; preview tetap tersedia bagi admin.

## Pengelolaan
Buka `/website`, pilih pengaturan section atau CRUD konten, lalu gunakan `/website/preview`. Isi berita dirender sebagai teks (bukan HTML arbitrary). Hero, Tentang, PPDB, kontak/footer, sosial, dan SEO dikelola melalui form biasa.

## Deployment
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=LandingPageSeeder --force
php artisan storage:link
npm ci && npm run build
php artisan optimize
```
