# Modul HRD / Kepegawaian

Modul HRD adalah bagian modular monolith e-Madrasah dan menggunakan `Personnel` sebagai master guru/tendik. Tabel utama: `personnel_attendances`, `personnel_attendance_late_remarks`, `personnel_leave_requests`, `personnel_payrolls`, `personnel_cash_advances`, dan `personnel_cash_advance_payments`.

## Alur

- **Absensi mandiri:** akun ditentukan melalui relasi `User::personnel`; server memvalidasi radius Haversine, jendela shift (termasuk lintas tengah malam), duplikasi, keterlambatan, pulang awal, dan lembur.
- **Izin/cuti:** pegawai mengajukan rentang tanggal dan lampiran privat. Approval atomik membuat/memperbarui absensi per hari menjadi sakit, izin, cuti, atau dinas luar.
- **Payroll:** mode tetap memakai gaji bulanan. Mode berbasis absensi menghitung `gaji harian = gaji bulanan / jumlah hari kalender bulan`, lalu `gaji pokok = gaji harian × hari dibayar`. Total bersih = gaji pokok + tunjangan − potongan manual − potongan terlambat − potongan kasbon. Contoh: Rp6.200.000 / 31 × 25 hari + Rp500.000 − Rp100.000 = Rp5.400.000 sebelum potongan terlambat/kasbon.
- **Keterlambatan:** potongan proporsional `gaji harian × menit terlambat / menit shift`; keterangan yang disetujui membuat potongan nol.
- **Kasbon:** pending → approved/rejected → disbursed → partially_paid → paid. Pembayaran memakai transaksi dan row lock sehingga sisa tidak negatif. Histori pembayaran tidak dihapus.
- **Self-service:** Absensi Saya, pengajuan sendiri, slip sendiri, dan kasbon sendiri selalu mengambil `Personnel` dari akun backend.

## Pengaturan

Seluruh setting memakai `ApplicationSettingService`: koordinat/radius, 1–3 shift, batas check-in, batas terlambat, face recognition, payroll berbasis absensi, potongan terlambat, dan potongan kasbon. Face recognition default nonaktif; provider biometrik belum disertakan sehingga GPS/manual tetap berfungsi tanpa menyimpan payload biometrik.

## Otorisasi dan audit

Permission HRD didefinisikan di `config/permissions.php`; role HRD memperoleh akses operasional, guru hanya data sendiri, operator tidak memperoleh payroll. Aktivitas kritis dicatat tanpa nominal sensitif rinci. Lampiran izin berada pada disk privat.

## Tidak dipindahkan

Bon Sparepart Karyawan tidak dipindahkan karena bergantung pada Product/StockMovement/Finance milik POSLaravel dan tidak mempunyai padanan inventory di e-Madrasah. Finance POS juga tidak disalin; payroll dan kasbon berdiri mandiri agar integrasi keuangan dapat ditambahkan melalui event tanpa coupling.
# Attendance Security

Absensi mandiri pegawai menerapkan pertahanan berlapis. Browser-based geolocation tidak dapat menjamin deteksi Fake GPS 100%. Sistem dapat memvalidasi geofence, batas akurasi, freshness, nonce, perangkat, dan (bila provider tersedia) identitas wajah. Perubahan lokasi tidak realistis atau sinyal lain hanya boleh menjadi anomali untuk tinjauan HRD, bukan bukti tunggal pelanggaran.

## Face Recognition

- Provider diakses melalui abstraction `FaceRecognitionService`; instalasi bawaan memakai provider `unavailable` dan gagal secara tertutup ketika verifikasi diwajibkan. Tidak ada fallback otomatis yang melewati wajah.
- Aktivasi menggunakan `hrd_attendance_face_enabled`; hasil provider harus tepat satu wajah, cocok dengan personnel akun login, dan memenuhi threshold provider (`hrd_face_confidence_threshold`).
- Bukti verifikasi terikat ke challenge dan berlaku sesuai `hrd_face_verification_ttl_seconds` (default 120 detik). Snapshot tidak disimpan oleh aplikasi setelah request selesai dan tidak masuk audit.
- Capability liveness dilaporkan provider. Liveness hanya diwajibkan/dinilai bila provider benar-benar mendukungnya; instalasi bawaan tidak mengklaim liveness maupun anti-spoof foto/layar.

## Location Lock

Titik pusat menggunakan `hrd_attendance_latitude` dan `hrd_attendance_longitude`, radius default 20 meter, akurasi maksimum default 50 meter, dan umur lokasi maksimum default 30 detik. Browser meminta lokasi high-accuracy baru tepat sebelum submit dengan cache dimatikan. Server—bukan JavaScript—menghitung jarak Haversine dan menolak koordinat, akurasi, atau timestamp yang tidak valid. Waktu masuk, pulang, tanggal, keterlambatan, dan lembur selalu berasal dari jam server.

## Anti Replay dan Perangkat

Challenge default berlaku 60 detik, menyimpan hash nonce, dan terikat pada user, personnel, hash sesi, aksi check-in/check-out, serta hash UUID perangkat. Challenge dikunci `lockForUpdate`, ditandai `used_at` dalam transaction yang sama dengan absensi, dan constraint unik attendance menjadi proteksi terakhir terhadap double submit. CSRF, autentikasi sesi normal, dan rate limit tetap aktif.

UUID perangkat pseudonymous dibuat di browser; server hanya menyimpan SHA-256 beserta nama/browser/platform yang mudah dipahami, hash user-agent untuk audit, status trusted/revoked, dan waktu penggunaan. Default maksimum dua perangkat dan perangkat baru langsung trusted; approval dapat diwajibkan. IP hanya metadata audit dan tidak pernah menjadi bukti lokasi tunggal.

## Audit, risiko, dan privasi

Audit mencatat hasil, IP, device, akurasi, jarak hasil server, bukti verifikasi, challenge, dan risk flag tanpa citra biometrik. Koordinat merupakan data sensitif dan detailnya hanya boleh dibuka dengan permission `personnel-attendance.view-location-audit`. Permission override lokasi/wajah hanya ditujukan kepada HRD berwenang; koreksi manual harus tetap berlabel `manual` dan memiliki alasan.

Mitigasi Fake GPS yang benar-benar diterapkan adalah geofence, GPS accuracy, freshness, face recognition opsional, nonce single-use, server timestamp, device binding, rate limiting, audit, dan risk detection. Browser tidak menyediakan attestation sumber GPS: aplikasi secara teknis tidak dapat memastikan koordinat tidak dimanipulasi, dan tidak mengklaim anti-spoof 100%.
