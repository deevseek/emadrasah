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
