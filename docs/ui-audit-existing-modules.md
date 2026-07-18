# Audit UI Modul Existing e-Madrasah

Status audit: BELUM SELESAI — JANGAN MERGE, karena environment tidak dapat mengambil dependency dari GitHub/Packagist secara penuh sehingga aplikasi dan browser smoke test belum dapat dijalankan sampai selesai.

## Sumber kebenaran

- Branch kerja lokal dibuat dari commit lokal terbaru karena `git fetch origin` gagal dengan `CONNECT tunnel failed, response 403`.
- Inventaris route bersumber dari `routes/web.php` dan `routes/finance.php` karena `php artisan route:list` belum dapat dijalankan tanpa `vendor/autoload.php`.
- Inventaris view menemukan 214 file Blade di `resources/views`.
- Role aktual dibaca dari `database/seeders/RolePermissionSeeder.php`.

## Modul aktual yang ditemukan

| Modul aktual | Route utama | Role/permission utama | File view utama | Status sebelum | Masalah ditemukan | Status setelah |
|---|---|---|---|---|---|---|
| Dashboard | `dashboard` | `dashboard.view` | `resources/views/dashboard.blade.php` | Perlu perbaikan | Section Keuangan Operasional berada setelah penutup kontainer dashboard dan tidak dibatasi permission; grid/kartu berpotensi melebar pada teks panjang. | Section dibatasi permission dan gaya global wrapping diperkuat. |
| Fondasi/Data Madrasah | `school-profile.*`, `academic-years.*`, `semesters.*`, `users.*` | permission masing-masing resource | `resources/views/foundation/**` | Perlu audit browser | Mengandalkan app shell global; risiko overflow tabel/form. | App shell dan table wrapping global diperkuat. |
| Data Siswa/Wali/PPDB | `students.*`, `guardians.*`, `student-enrollments.*` | permission masing-masing resource | `resources/views/students/**`, `resources/views/guardians/**` | Perlu audit browser | Risiko tabel panjang pada mobile. | CSS global tabel/pagination diperkuat. |
| Akademik | `classrooms.*`, `subjects.*`, `teaching-assignments.*`, `schedules.*` | permission masing-masing resource | `resources/views/academic/**`, `resources/views/teaching-assignments/**`, `resources/views/schedules/**` | Perlu audit browser | Sidebar menjadi sumber navigasi utama. | Overlay/drawer mobile dan active state tetap berbasis route name. |
| Kehadiran Pegawai & Siswa | `employee-attendances.*`, `employee-leaves.*`, `work-schedules.*`, `attendance-reports.*`, `student-attendances.*` | permission masing-masing resource | `resources/views/attendance/**` | Perlu audit browser | Print view perlu bebas app shell; tabel laporan panjang. | CSS print global menyembunyikan shell; tabel dibuat lebih aman. |
| BTAQ | `btaq.*`, `btaq-groups.*`, `btaq-sessions.*`, `btaq-reports.*` | permission BTAQ | `resources/views/btaq/**` | Perlu audit browser | Navigasi panjang perlu sidebar scroll stabil. | Sidebar tetap scroll vertikal dan mobile close saat link diklik. |
| Penilaian/Rapor | `assessments.*`, `report-cards.*` | permission assessment/grade/report | `resources/views/assessment-module/**`, `resources/views/assessments/**` | Perlu audit browser | Tabel leger/laporan panjang berisiko overflow. | CSS global tabel/pagination diperkuat. |
| Keuangan Siswa | `student-finance.*` | permission student finance | `resources/views/finance/**` | Perlu audit browser | Kartu nominal panjang berisiko keluar kartu. | Wrapping global dan dashboard grid responsif diperkuat. |
| Keuangan Operasional | `operational-finance.*` | permission operational finance | `resources/views/operational-finance/**` | Perlu audit browser | Kartu dashboard operational finance tampil di luar kontainer dan tanpa gate. | Dipindahkan ke alur layout dashboard serta dibungkus `@can`. |
| Payroll Pegawai | `payroll.*` | permission payroll | `resources/views/payroll/**` | Perlu audit browser | Kartu nominal dan slip berpotensi overflow. | Wrapping global diperkuat. |
| Autentikasi | `login`, `password.*` | guest/auth | `resources/views/auth/**` | Perlu audit browser | Belum dapat divalidasi visual. | Tidak diubah. |
| Error page | Laravel error views existing | n/a | `resources/views/errors/**` bila ada | Perlu audit browser | Belum dapat divalidasi visual. | Print/app shell CSS tidak memengaruhi error page. |

## Matrix navigasi dan permission aktual

| Role | Menu | Route | Expected | Actual sebelum | Setelah Fix |
|---|---|---|---|---|---|
| Super Admin | Semua grup sidebar yang memiliki permission | route sesuai menu | 200 | Belum tervalidasi browser | Menu tetap gated via Gate/Gate::any. |
| Admin Madrasah | Data Madrasah, Akademik, Kehadiran, BTAQ, Keuangan, Payroll, Akses | route sesuai permission | 200 | Belum tervalidasi browser | Menu tetap gated via Gate/Gate::any. |
| Operator | Data master/akademik/kehadiran/BTAQ/keuangan tertentu | route sesuai permission | 200 | Belum tervalidasi browser | Tidak ada permission diubah. |
| Tata Usaha | Data siswa, kehadiran, laporan, keuangan tertentu | route sesuai permission | 200 | Belum tervalidasi browser | Tidak ada permission diubah. |
| Kepala Madrasah | Monitoring, laporan, approval tertentu | route sesuai permission | 200/403 sah | Belum tervalidasi browser | Tidak ada permission diubah. |
| Bendahara | Keuangan siswa, operasional, payroll | route sesuai permission | 200 | Belum tervalidasi browser | Dashboard operational finance dibatasi permission. |
| Guru Kelas | Akademik kelas, absensi siswa, rapor, slip | route sesuai permission | 200/403 sah | Belum tervalidasi browser | Tidak ada permission diubah. |
| Guru Mata Pelajaran | Jadwal/jurnal/nilai/slip | route sesuai permission | 200/403 sah | Belum tervalidasi browser | Tidak ada permission diubah. |
| Guru BTAQ/Murobi | BTAQ, jadwal, jurnal, slip | route sesuai permission | 200/403 sah | Belum tervalidasi browser | Tidak ada permission diubah. |
| Wali Murid | Dashboard terbatas | dashboard | 200/403 sah | Belum tervalidasi browser | Tidak ada permission diubah. |

## Catatan browser, console, asset, dan screenshot

Browser automation belum dapat dijalankan karena dependency Composer belum terpasang penuh. Screenshot before/after belum tersedia sebagai artifact. Console dan network belum dapat dinyatakan bersih.

## Perbaikan yang diterapkan

- Overlay sidebar mobile memakai class khusus yang benar-benar bereaksi terhadap class `mobile-sidebar-open` pada elemen HTML.
- Tombol sidebar diberi `type="button"` dan `aria-label`.
- Klik menu sidebar menutup drawer mobile.
- Escape menutup drawer mobile.
- Submit form men-disable tombol submit untuk mengurangi submit ganda.
- Container halaman diberi `min-width: 0`, max width, dan wrapping lebih aman.
- CSS print global menyembunyikan sidebar/topbar dari tampilan cetak.
- Section Keuangan Operasional dashboard dibatasi `@can('operational-finance-dashboard.view')`.

## Masalah belum selesai

- Audit browser nyata per role dan viewport belum selesai.
- Screenshot before/after belum dihasilkan.
- `php artisan route:list`, `view:cache`, `route:cache`, test, Pint, dan Vite build belum dapat dijalankan penuh selama dependency belum selesai dipasang.
- Push branch ke GitHub gagal karena akses network ke GitHub diblokir oleh CONNECT tunnel 403.
