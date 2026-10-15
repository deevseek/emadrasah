# Audit dan perbaikan backend RFID ESP8266 v2.4.2

Audit dilakukan terhadap `main` pada commit `b9f3b80033f60aecb941ccd3b1beff6e07415635`. Informasi perangkat tanggal 9 Oktober 2026 berasal dari laporan pengguna; pekerjaan ini tidak membaca database produksi atau menghubungi router/perangkat aktif.

## Temuan

1. `RfidDevice::isOnline()` memeriksa perangkat aktif dan `last_seen_at` dalam 75 detik, bukan kondisi radio WiFi. `AuthenticateRfidDevice` memperbarui waktu tersebut pada setiap request terautentikasi, termasuk polling command serta request yang kemudian gagal validasi/throttle. Tulisan dashboard sebelumnya menyebut waktu itu sebagai heartbeat dan memberi label “Terputus”, sehingga melampaui bukti yang tersedia.
2. Heartbeat setiap 30 detik menyisakan toleransi 75 detik: dua heartbeat terlambat, timeout HTTP, atau jeda reconnect dapat membuat perangkat tidak terlihat walaupun WiFi tersambung. Autentikasi ditolak menghasilkan 401, payload invalid 422, kuota habis 429, dan kegagalan backend/proxy dapat menghasilkan 5xx atau redirect di luar Laravel. Tidak ada satu status tersebut yang membuktikan WiFi putus.
3. Throttle sebelumnya menggunakan IP pemanggil, dengan kuota 60/menit untuk attendance dan 120/menit untuk heartbeat/polling/completion. Beberapa perangkat di balik NAT dapat berbagi kuota. Patch mempertahankan kuota dan endpoint, mengganti key menjadi ID perangkat terautentikasi, dan mengurutkan autentikasi sebelum throttle Laravel. Kegagalan validasi RFID tetap mengembalikan JSON walaupun firmware tidak mengirim Accept.
4. Retry absensi sebelumnya membuat event baru, serta mengubah respons dari `ATTENDANCE_CREATED` menjadi `ALREADY_ATTENDED`. Mengunci hasil query absensi yang belum ada tidak cukup untuk menjamin serialisasi lintas reader pada semua database.
5. `LiveAttendanceService` di main sudah menyertakan `is_active` ketika menghidrasi reader. Bug proyeksi yang pernah membuat semua reader offline sudah diperbaiki; patch ini tidak mengulang perubahan tersebut. `RfidWriterService::onlineWriter()` tetap menggunakan tipe writer dan jendela last_seen 75 detik, termasuk mode idle.
6. IP laporan yang sama pada Reader 05/04 (`192.168.0.157`), 06/03 (`192.168.0.112`), dan 08/09 (`192.168.0.162`) hanya dugaan yang perlu diverifikasi. LAN berbeda dapat menggunakan IP privat sama. RSSI -89 dBm Reader 05 menunjukkan sinyal lemah pada laporan itu, bukan bukti akar masalah heartbeat atau konflik IP.

## Perubahan dan kontrak firmware

Heartbeat lama tetap menggunakan `POST /api/rfid/device/heartbeat` dengan `firmware_version`, `ip`, `rssi`, `mode`, serta header X-Device-ID/X-Device-Token. Reader dan Writer tetap terautentikasi dengan kontrak lama. Mode telemetry tidak mengubah `device_type` atau hak akses.

Field opsional baru: MAC/BSSID valid; gateway/subnet IPv4; kanal 1–14; free_heap dan disconnect_count 0–4294967295; disconnect_reason numerik 0–65535. Kolom baru nullable. Field diagnostik yang tidak dikirim mempertahankan laporan terakhir; null eksplisit mengosongkan field. Data ini adalah snapshot heartbeat terakhir, bukan pemantauan WiFi saat ini. MAC dinormalisasi ke huruf besar dan pemisah titik dua. Pengamatan per Device ID/MAC menyimpan waktu pertama dan terakhir, tanpa token.

`last_seen_at` tetap berarti request terautentikasi terakhir agar Writer lama tidak berubah perilakunya. `last_heartbeat_at` hanya diperbarui sesudah heartbeat valid berhasil disimpan. Data lama tidak di-backfill dengan waktu yang tidak diketahui. Dashboard menampilkan status terakhir terlihat di server dan waktu heartbeat valid secara terpisah.

Attendance tetap `POST /api/rfid/attendance` dengan `uid` dan `card_token`. `request_id` opsional adalah string 1–128 karakter: karakter pertama alfanumerik, berikutnya alfanumerik/titik/underscore/titik dua/minus. Key bersifat case-sensitive, termasuk pada MySQL. Firmware harus membuat ID unik untuk scan baru dan mempertahankan ID serta payload yang sama selama retry; ID yang diulang sesudah reboot akan dianggap replay atau konflik, bukan scan baru.

Receipt mengunci baris perangkat yang sudah ada sebelum mengecek key, dengan unique constraint `(rfid_device_id, request_id)`. Respons HTTP dan body bisnis lengkap disimpan bersama event/presensi dalam satu transaksi. Payload hash meliputi `uid`, `card_token`, dan `scanned_at` sesudah normalisasi request Laravel; nilai token tidak disimpan di receipt. Request identik mengembalikan status dan body asli, termasuk event_id serta penolakan 403/404/422. Key sama dengan payload berbeda menghasilkan 409 `REQUEST_ID_CONFLICT`, tanpa event baru. Key valid pada request yang gagal validasi juga menghasilkan receipt idempotent. Key invalid/tidak ada/null mengikuti perilaku lama tanpa receipt.

401/429 terjadi sebelum pemrosesan bisnis sehingga tidak disimpan sebagai receipt. Exception server membatalkan receipt, event, dan presensi; retry setelah error dapat diproses lagi. Autentikasi tetap diperiksa pada setiap retry. Request-ID tidak boleh menjadi cara melewati perangkat nonaktif atau token invalid. Tidak ada TTL/penghapusan otomatis receipt yang dapat membuka kembali key lama.

`scanned_at` opsional menerima string tanggal valid maksimum 64 karakter; format ISO 8601 dengan zona waktu dianjurkan. Nilai disimpan sebagai metadata `device_scanned_at` pada receipt, bukan sebagai tanggal akademik. `attendance_date`, `StudentAttendance.scanned_at`, dan waktu event tetap mengikuti waktu penerimaan server serta timezone aplikasi. Scan baru dari antrean lama tetap mengikuti kebijakan server yang sudah ada; tidak ada backdating, perubahan kalender akademik, atau koreksi waktu otomatis. Retry key yang telah tersimpan, termasuk pada hari berikutnya, tetap mengembalikan hasil asli. Request tanpa request_id tetap kompatibel dan tidak memakai waktu perangkat untuk presensi.

Untuk scan serentak lintas Device ID, service attendance mengunci siswa sebelum mencari/membuat presensi harian. Ini berlaku juga untuk firmware lama tanpa request_id. Respons/event legacy tetap berbeda untuk setiap scan, sementara presensi harian tidak berganda.

## Audit jaringan read-only

Jalankan setelah migration disetujui dan diterapkan oleh pengelola:

```sh
php artisan rfid:network-audit
```

Command hanya membaca Device ID, IP, MAC, gateway, subnet, BSSID, RSSI, firmware, heartbeat terakhir, serta riwayat MAC. Setiap kelompok IP sama diberi label **dugaan konflik yang perlu diverifikasi pada DHCP/ARP router; bukan kepastian konflik IP**. Gateway/subnet/BSSID membantu penyelidikan, tetapi kesamaannya tidak membuktikan satu LAN. Laporan lama juga dapat sudah tidak berlaku.

Lebih dari satu MAC dalam riwayat Device ID ditandai untuk pemeriksaan identitas; penggantian hardware juga dapat menjelaskan riwayat tersebut. Pendeteksian hanya mencakup MAC yang pernah dikirim dan dicatat server, bukan discovery perangkat yang belum pernah mengirim heartbeat. Command tidak mengubah IP, Device ID, token, atau konfigurasi router.

## Migration additive

- `2026_10_09_000000_add_rfid_network_diagnostics.php`: kolom telemetri nullable dan last_heartbeat_at, serta tabel pengamatan MAC.
- `2026_10_09_000001_create_rfid_attendance_requests.php`: tabel receipt idempotensi dan unique constraint perangkat/request_id.

Penerapan migration adalah langkah operasional setelah review. Jangan mengaktifkan kode baru sebelum schema tersedia. Rollback migration menghapus telemetri/riwayat dan receipt baru; key yang receipt-nya dihapus tidak lagi terlindungi terhadap replay. Migration tidak mengubah credential atau IP dan tidak menyentuh tabel modul lain.

## Pengujian

Test utama memakai SQLite in-memory. Test race memakai dua proses HTTP terpisah dan MariaDB 11.4 lokal: transaksi pertama ditahan sebelum insert event, kedua worker telah berjalan bersamaan, dan test membuktikan request kedua menunggu kunci sebelum commit. Database race wajib bernama `rfid_test_concurrency`; test melewati skenario race pada SQLite dan tidak menjalankan migration race pada database lain.

```sh
php vendor/bin/phpunit --filter Rfid
```

Untuk race, gunakan konfigurasi PHPUnit khusus database MySQL/MariaDB lokal tersebut dengan DB_CONNECTION=mysql, DB_DATABASE=rfid_test_concurrency, APP_ENV=testing, CACHE_STORE=array, DB_URL kosong, serta credential lokal. Jalankan:

```sh
php vendor/bin/phpunit -c /path/to/phpunit-mysql.xml --filter RfidAttendanceConcurrencyTest
```

Test race membuat ulang dan menghapus database test khusus; jangan arahkan ke database aktif. PHP 8.2 dan 8.3 diuji memakai dependency Laravel 12 dari repository. Hasil eksekusi sebenarnya dan daftar lengkap file dicantumkan pada laporan perubahan/PR. Test lama yang diperbaiki terbatas pada fixture RFID: enum semester, penyegaran cache setting, representasi DATE SQLite untuk kontrak jurnal, dan escaping JSON URL Writer. Tidak ada perubahan implementasi jurnal atau modul lain.

## Hasil eksekusi

| Pemeriksaan | Hasil |
| --- | --- |
| PHP 8.2.34, PHPUnit filter RFID, SQLite | 64 lulus, 3 race dilewati; 471 assertion; tidak ada failure/error |
| PHP 8.3.35, PHPUnit filter RFID, SQLite | 64 lulus, 3 race dilewati; 471 assertion; tidak ada failure/error |
| PHP 8.2.34, MariaDB 11.4, dua proses HTTP | 3 race lulus; 28 assertion |
| PHP 8.3.35, MariaDB 11.4, dua proses HTTP | 3 race lulus; 28 assertion |
| PHP 8.2.34, MariaDB 11.4, feature heartbeat/attendance/network | 36 lulus; 350 assertion |
| PHP lint seluruh file PHP berubah pada PHP 8.3 | 22 file lulus |
| git diff --check | Lulus |

Dengan race yang dijalankan terpisah, seluruh 67 test RFID tercakup pada masing-masing versi PHP. Verifikasi feature MariaDB tambahan mengecualikan test round-trip schema yang menggunakan transaksi SQLite; migration additive diterapkan pada MariaDB oleh test tersebut dan test race. Semua hasil diperoleh pada container lokal. Suite modul lain tidak dijalankan.

PHPUnit 11.5.56 menampilkan dua deprecation metadata doc-comment dari `Hrd/AttendanceServiceTest::test_invalid_location_is_rejected` dan `SchoolProfileTest::test_profile_validation` saat discovery. Keduanya di luar ruang lingkup; tidak ada error/failure RFID pada verifikasi akhir. Eksekusi awal menemukan fixture lama usang dan sudah diperbaiki; verifikasi akhir yang dilaporkan memakai patch final. Fixture validasi juga tidak lagi mengasumsikan auto-increment event selalu 1, karena transaksi MySQL tidak mereset sequence.

## File yang berubah

- `app/Console/Commands/RfidNetworkAudit.php`
- `app/Http/Controllers/Api/RfidAttendanceController.php`
- `app/Http/Controllers/Api/RfidDeviceCommandController.php`
- `app/Http/Middleware/AuthenticateRfidDevice.php`
- `app/Http/Requests/Rfid/HeartbeatRequest.php`
- `app/Http/Requests/Rfid/RecordRfidAttendanceRequest.php`
- `app/Models/RfidDevice.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/Academic/RfidAttendanceService.php`
- `app/Services/Rfid/RfidAttendanceRequestService.php`
- `app/Services/Rfid/RfidHeartbeatService.php`
- `bootstrap/app.php`
- `database/migrations/2026_10_09_000000_add_rfid_network_diagnostics.php`
- `database/migrations/2026_10_09_000001_create_rfid_attendance_requests.php`
- `docs/rfid-resilience-audit.md`
- `resources/views/academic/attendance/index.blade.php`
- `resources/views/settings/rfid-devices/index.blade.php`
- `resources/views/students/show.blade.php`
- `routes/api.php`
- `tests/Feature/RfidAttendanceApiTest.php`
- `tests/Feature/RfidAttendanceConcurrencyTest.php`
- `tests/Feature/RfidAttendanceLiveTest.php`
- `tests/Feature/RfidCardRewriteTest.php`
- `tests/Feature/RfidNetworkMonitoringTest.php`
- `tests/Feature/RfidWriterPageTest.php`
- `tests/Support/rfid-concurrent-request.php`
