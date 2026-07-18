# Acceptance Modul Assessment dan Finance

Tanggal: 18 Juli 2026

## Assessment
| Skenario | Expected result | Actual result | Status |
|---|---|---|---|
| Guru membuka input nilai | Hanya kelas/mapel berwenang terlihat | Perlu verifikasi browser/staging | Belum diverifikasi visual |
| Simpan draft nilai | Nilai tersimpan ke `student_scores` | Tersedia service backend dan validasi | Siap diuji |
| Generate rapor | Rapor berisi snapshot nilai/kehadiran/BTAQ | Tersedia service backend | Siap diuji |
| Export leger | CSV berisi kelas dan jumlah siswa | Tersedia export | Siap diuji |

## Finance
| Skenario | Expected result | Actual result | Status |
|---|---|---|---|
| Buat akun kas | Akun tersimpan dengan saldo awal | Form Request dan controller aktif | Siap diuji |
| Catat pemasukan/pengeluaran | Draft/posted tersimpan dan saldo berubah saat posting | Service transaction tersedia | Siap diuji |
| Transfer kas | Mutasi keluar/masuk atomik | Service transaction tersedia | Siap diuji |
| Export laporan | CSV sesuai filter dan berisi total | Export diperbaiki | Siap diuji |

Screenshot acceptance belum tersedia karena tidak ada browser automation yang dijalankan pada sesi ini.
