# Laporan Reset Rebuild e-Madrasah

## Tujuan

Reset source code ini menghapus implementasi bisnis lama dan menyisakan fondasi Laravel yang bersih untuk instalasi baru. Reset tidak menyediakan migration penghancur tabel production dan ditujukan untuk `php artisan migrate:fresh --seed` pada database kosong.

## Modul yang dihapus

Profil dan pengaturan madrasah berbasis database; tahun ajaran dan semester; pengguna versi lama; guru/pegawai; siswa, wali, dan pendaftaran; kelas, mata pelajaran, penugasan, jadwal, impor, serta jurnal; absensi pegawai dan siswa; BTAQ; penilaian dan rapor; keuangan siswa dan operasional; payroll; inventaris/aset; backup; serta seluruh laporan, workflow, seeder, migration, policy, service, view, route, dan test khusus modul tersebut.

## Fondasi yang dipertahankan

- Autentikasi session: login, logout, lupa/reset/ganti password, rate limiting, serta pemeriksaan akun aktif.
- Model pengguna minimal, factory, dan administrator development.
- Spatie Permission dengan satu role dan permission.
- Spatie Activitylog sebagai infrastruktur generik.
- Cache, queue database, session, dan password reset Laravel.
- Shell UI Blade, komponen UI generik, CSS, JavaScript, Tailwind, Vite, dan Livewire.
- Dashboard fondasi tanpa query domain.

## Tabel tersisa

`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`, `activity_log`, dan `login_histories`.

`login_histories` dipertahankan karena merupakan audit keamanan autentikasi, bukan data modul bisnis.

## Route web tersisa

| Metode | URI | Nama |
|---|---|---|
| GET | `/` | — (redirect ke `/dashboard`) |
| GET | `/login` | `login` |
| POST | `/login` | `login.store` |
| POST | `/logout` | `logout` |
| GET | `/forgot-password` | `password.request` |
| POST | `/forgot-password` | `password.email` |
| GET | `/reset-password/{token}` | `password.reset` |
| POST | `/reset-password` | `password.store` |
| GET | `/password/change` | `password.change` |
| PUT | `/password/change` | `password.change.update` |
| GET | `/dashboard` | `dashboard` |

Laravel juga menyediakan endpoint health `/up`.

## Hak akses tersisa

- Role: `super-admin`.
- Permission: `dashboard.view`.
- Role `super-admin` memiliki `dashboard.view`.

## File UI utama yang dipertahankan

- `resources/css/app.css` dan `resources/js/app.js`: warna, tipografi, layout sidebar, overlay, dan perilaku responsive lama.
- `resources/views/auth/*`: tampilan autentikasi lama.
- `resources/views/components/app-layout.blade.php` dan `resources/views/layouts/app.blade.php`: shell backoffice lama, dilepas dari model domain.
- `resources/views/components/ui/*`: komponen generik card, stat card, empty state, input, select, textarea, button, badge, table, modal, alert, pagination, dan page header.

## Catatan pembangunan berikutnya

Bangun modul baru secara bertahap sebagai modular monolith. Tambahkan route, permission, migration, service/action, policy, Form Request, dan test hanya ketika kebutuhan modul telah ditentukan. Jangan mengembalikan dependensi identitas atau badge periode pada tabel bisnis hanya untuk kebutuhan layout.
