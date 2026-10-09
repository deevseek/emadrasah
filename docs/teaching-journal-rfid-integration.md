# Integrasi Jurnal Mengajar dengan Absensi RFID Harian

Branch kerja: `fix/journal-daily-rfid`.
Sumber: `origin/main`, commit `9570e77b2828816a7bc82b0e4d36fe8b89b14292`, diperiksa ulang melalui fetch pada 9 Oktober 2026.

## Akar masalah yang diverifikasi

- RFID sudah menyimpan absensi harian dan memakai kunci siswa serta transaksi; unique constraint `attendance_unique` mencakup siswa, rombel, dan tanggal.
- `TeachingJournalService::save()` sebelumnya menulis ulang `student_attendances` dan menghapus semua snapshot jurnal sebelum membuat ulang detailnya.
- Form menggunakan Hadir sebagai nilai awal tanpa catatan harian. Pergantian rombel hanya mengganti elemen HTML; tanggal tidak memuat ulang absensi.
- Form edit memakai snapshot jurnal, sedangkan form tambah memakai absensi harian, tanpa resolver bersama.
- RFID sebelumnya mengunci semua kehadiran jurnal sebagai Hadir sehingga pengecualian per pelajaran tidak dapat dicatat.
- Laporan menghitung seluruh siswa selain Hadir sebagai Tidak Hadir; rumus tersebut tidak cocok untuk status sementara.

## Alur setelah perbaikan

1. Endpoint `POST /api/rfid/attendance` tetap menggunakan autentikasi perangkat, payload, idempotensi `request_id`, respons, dan locking yang sudah tersedia.
2. Scan mencatat satu `student_attendances` dengan status `present`, sumber `rfid`, waktu scan, dan reader. Keanggotaan RFID harus sesuai tahun ajaran aktif dan masa keanggotaan.
3. Resolver membaca absensi harian untuk siswa, rombel, tahun ajaran, semester, tanggal, dan jadwal mata pelajaran yang relevan.
4. Saat jurnal disimpan, resolver menyediakan status awal; input status otomatis dari browser tidak dipercaya. Detail jurnal disimpan melalui upsert kelompok dalam transaksi, tanpa menulis absensi harian.
5. Scan dan penyimpanan absensi harian manual menyinkronkan detail jurnal dengan `origin=daily`. Koreksi guru (`manual`) dan snapshot lama (`legacy`) tidak ditimpa.
6. Kunci siswa diambil berurutan sebelum penyimpanan jurnal/absensi. Pembacaan absensi di dalam transaksi memakai locking read agar tidak memakai snapshot MySQL yang sudah tertinggal setelah menunggu kunci.

Guru dapat memilih **Ikuti absensi harian**, **Koreksi khusus pelajaran**, atau **Pertahankan catatan tersimpan**. Koreksi membutuhkan salah satu empat status resmi dan alasan; pelaku, waktu koreksi, dan perubahan dicatat. Pemulihan ke sumber harian juga diaudit.

Pergantian tanggal atau rombel pada form memuat ulang roster dan status. Perubahan konteks jurnal tersimpan mencatat snapshot lama dalam audit; detail siswa dari rombel sebelumnya diarsipkan dengan soft delete. Koreksi dari konteks lama tidak terbawa ke konteks baru. Pengeditan pada konteks yang sama mempertahankan koreksi dan ID detail.

## Belum scan dan keterlambatan

- Tanpa absensi harian: `pending`, ditampilkan **Belum Tercatat**. Status ini hanya ada pada enum jurnal; empat status resmi absensi harian tetap sama.
- Sakit, Izin, dan Alpa yang sudah dikonfirmasi pada absensi harian menjadi status awal jurnal.
- RFID yang waktunya setelah awal pelajaran tidak otomatis menjadikan pelajaran itu Hadir. Status awal tetap Belum Tercatat untuk dikonfirmasi guru; absensi harian tetap Hadir.
- Pelajaran setelah kedatangan menjadi Hadir apabila jadwal menunjukkan kedatangan sebelum pelajaran dimulai.
- Jika mata pelajaran mempunyai beberapa sesi pada hari yang sama, resolver memakai awal sesi paling dini secara konservatif. Data `lesson_number` berupa teks bebas dan jadwal belum mempunyai pemetaan nomor jam ke sesi; sesi berikutnya yang ambigu memerlukan koreksi guru.
- Jika jadwal mata pelajaran belum tersedia, dipakai awal jadwal rombel yang paling dini. Jika tidak ada jadwal sama sekali, batas konservatif adalah **07.00 waktu aplikasi**. Siswa yang datang setelah batas itu tetap bisa scan, tetapi kehadiran per pelajaran memerlukan konfirmasi. Timezone aplikasi mengikuti pengaturan yang sudah ada, dengan default Asia/Jakarta.
- Tidak ada perubahan otomatis menjadi Alpa berdasarkan lewatnya waktu.

HTML, PDF, dan DOCX menghitung Tidak Hadir hanya dari Sakit + Izin + Alpa. Belum Tercatat ditampilkan terpisah pada detail dan dalam kolom Keterangan laporan. Bentuk tabel serta placeholder template DOCX lama tetap dipertahankan.

## File yang diubah

| File | Tujuan |
|---|---|
| `app/Enums/JournalAttendanceStatus.php` | Enum jurnal dengan Belum Tercatat tanpa mengubah enum absensi harian. |
| `app/Models/TeachingJournalAttendance.php` | Cast enum jurnal dan pengarsipan soft delete. |
| `app/Services/Academic/JournalAttendanceResolver.php` | Roster berdasarkan konteks, resolusi absensi harian/jadwal, dan sinkronisasi detail otomatis. |
| `app/Services/Academic/TeachingJournalService.php` | Pisahkan data harian, upsert detail, koreksi guru, transaksi, locking, dan audit perubahan konteks. |
| `app/Services/Academic/RfidAttendanceService.php` | Validasi keanggotaan periode aktif dan sinkronisasi jurnal setelah pencatatan RFID. |
| `app/Services/Academic/AcademicEntryService.php` | Sinkronisasi setelah absensi harian manual serta urutan kunci siswa yang konsisten. |
| `app/Http/Controllers/Academic/TeachingJournalController.php` | Endpoint roster/status dan pilihan periode; hapus pembacaan sumber absensi yang terpisah dari form. |
| `app/Http/Requests/Academic/TeachingJournalRequest.php` | Validasi mode/status sementara dan batas tanggal tahun ajaran/semester. |
| `routes/academic.php` | Endpoint baca absensi jurnal dengan permission yang sudah ada. |
| `resources/views/academic/teaching-journals/form.blade.php` | Muat ulang data, sumber, kedatangan, dan koreksi dengan alasan. |
| `resources/views/academic/teaching-journals/show.blade.php` | Status sementara dan informasi sumber/koreksi. |
| `resources/views/academic/teaching-journals/report.blade.php` | Pisahkan status sementara dari jumlah ketidakhadiran. |
| `app/Services/Academic/TeachingJournalReportService.php` | Hitungan DOCX dan keterangan Belum Tercatat. |
| `database/migrations/2026_10_09_000000_add_journal_attendance_provenance.php` | Migration aditif untuk asal status, audit koreksi, kedatangan, dan arsip. |
| `tests/Feature/TeachingJournalIntegrationTest.php` | Skenario integrasi, akses, validasi, perpindahan konteks, riwayat, dan laporan. Perbaikan fixture lama untuk TrimStrings dan role guru. |
| `tests/Feature/RfidAttendanceApiTest.php` | Scan pagi deterministik serta sinkronisasi jurnal melalui endpoint RFID. |
| `tests/Feature/RfidAttendanceConcurrencyTest.php` | Jadikan tiga helper fixture protected agar dapat digunakan runner aman; perilaku test lama tetap sama. |
| `tests/Support/RfidSafeConcurrencyTest.php` | Runner yang hanya memakai migrate biasa pada database sementara kosong, serta test konkurensi dengan jurnal tersimpan. |
| `docs/teaching-journal-rfid-integration.md` | Laporan audit, implementasi, pengujian, dan penerapan. |

## Migration dan penerapan

Migration baru menambahkan `origin` (default `legacy`), `daily_source`, `arrival_at`, `corrected_by`, `corrected_at`, dan `deleted_at` pada `teaching_journal_attendances`. Data lama tetap memiliki status/keterangan yang sama; tidak ada rekonsiliasi massal.

Setelah review dan backup database, terapkan kode beserta migration melalui proses rilis yang berlaku, lalu jalankan `php artisan migrate --force`. Jalankan pembaruan cache aplikasi/rute sesuai proses rilis. Pastikan jadwal pelajaran dan timezone terisi benar. Uji satu scan dan beberapa jurnal pada staging sebelum penggunaan produksi.

Tidak dilakukan deployment, merge, migration produksi, `migrate:fresh`, atau `db:wipe` dalam pekerjaan ini. Database MariaDB pengujian dibuat sementara, kosong, dan terpisah; runner aman menjalankan satu skenario per database baru.

Rollback ke aplikasi lama memerlukan penanganan eksplisit untuk jurnal berstatus pending karena enum lama tidak mengenal status tersebut. Jangan mengubah pending menjadi Hadir/Alpa secara massal untuk memaksakan rollback. Migration aditif juga dapat memerlukan waktu/kunci DDL pada tabel produksi besar.

## Pengujian sebenarnya

Runtime: PHP **8.3.35**, PHPUnit **11.5.56**, SQLite memori untuk suite utama, dan MariaDB **11** untuk konkurensi. PHP 8.2 belum diuji langsung; kode tidak menggunakan fitur bahasa di atas PHP 8.2. Tidak ada perubahan versi PHP/Laravel proyek.

Perintah suite utama:

```sh
php artisan test --compact --filter='TeachingJournal|Rfid|DeleteStudentRfidCard|AcademicTablesMigration|AttendanceHistoryView|StudentAttendanceXlsx' --log-junit work/final-junit.xml
```

Hasil: **99 PASS, 0 FAIL, 3 SKIP, 613 assertions**. Tiga SKIP adalah suite konkurensi asli yang membutuhkan MySQL; skenario yang sama dijalankan terpisah di MariaDB melalui runner aman, ditambah skenario jurnal tersimpan.

| Kelompok test | Hasil |
|---|---|
| TeachingJournalIntegrationTest | 23 PASS |
| RfidAttendanceApiTest | 20 PASS |
| RfidAttendanceLiveTest | 4 PASS |
| RfidCardRewriteTest | 13 PASS |
| RfidDeviceManagementTest | 3 PASS |
| RfidNetworkMonitoringTest | 15 PASS, termasuk dataset validasi |
| RfidWriterPageTest | 1 PASS |
| DeleteStudentRfidCardTest | 3 PASS |
| AcademicTablesMigrationTest | 2 PASS |
| AttendanceHistoryViewTest | 2 PASS |
| StudentAttendanceXlsxExportTest | 4 PASS |
| Unit RfidWriterTest | 2 PASS |
| Unit StudentRfidCardTest | 4 PASS, termasuk dataset UID |
| Unit TeachingJournalTemplateServiceTest | 3 PASS |
| Konkurensi: request_id identik | PASS, 10 assertions |
| Konkurensi: payload bertentangan pada request_id sama | PASS, 9 assertions |
| Konkurensi: dua reader tanpa request_id | PASS, 9 assertions |
| Konkurensi: jurnal otomatis dan pengecualian guru | PASS, 10 assertions |

Total pengujian terkait yang berhasil: **103 PASS** termasuk empat skenario MariaDB. Setiap skenario MariaDB memakai database baru bernama `rfid_test_concurrency`, `APP_ENV=testing`, driver mysql, serta koneksi khusus pengujian. Cara menjalankan:

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Support/RfidSafeConcurrencyTest.php --filter=NAMA_SKENARIO
```

Runner menolak database yang sudah memiliki tabel migrations. Empat nama skenario tersedia dalam runner tersebut dan kelas induknya. Ini memungkinkan pengujian tanpa mengeksekusi reset destruktif yang ada pada suite konkurensi lama.

Tambahan regresi AcademicPeriodTest: **5 PASS, 1 FAIL**. Kegagalan `test_activation_keeps_only_one_active_period_and_is_idempotent` (baris 75, jumlah tahun aktif 0, ekspektasi 1) direproduksi pada snapshot main awal dengan aset/kunci pengujian yang sesuai. Service aktivasi mengosongkan flag aktif melalui query lalu mencoba mengaktifkan kembali model yang masih menyimpan nilai true, sehingga Eloquent tidak menganggap flag berubah. Kode ini tidak diubah karena di luar integrasi jurnal/RFID.

Pint pada file implementasi/test yang relevan dan `git diff --check` berhasil. Syntax JavaScript form diperiksa menggunakan `node --check`. PDF benar-benar dihasilkan dalam feature test; hitungan diuji pada view yang menjadi sumber PDF dan XML hasil DOCX. Interaksi form belum diuji melalui browser otomatis. Dua peringatan PHPUnit tentang metadata doc-comment berasal dari test lama di modul lain.

## Review

Branch: https://github.com/deevseek/emadrasah/tree/fix/journal-daily-rfid
Perbandingan: https://github.com/deevseek/emadrasah/compare/main...fix/journal-daily-rfid
