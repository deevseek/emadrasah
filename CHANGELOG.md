# Changelog

## 2026-07-18

### Added
- Dokumentasi database, ERD, deployment, testing, acceptance, dan modul untuk Penilaian/Rapor serta Keuangan Operasional.
- Permission alias granular `assessment.*`, `report-card.*`, dan `finance.*` agar kompatibel dengan requirement end-to-end.

### Changed
- Form Request Keuangan Operasional kini memiliki rules validasi nyata untuk akun kas, kategori, transaksi, transfer, anggaran, approval, rejection, cancellation, rekonsiliasi, closing, dan filter laporan.
- Export laporan Keuangan Operasional kini men-stream CSV sesuai filter dan menyertakan isi data serta total, bukan header kosong.

### Fixed
- Mengurangi risiko endpoint write tanpa validasi eksplisit pada Modul Keuangan Operasional.

### Database changes
- Tidak ada migration baru pada perubahan ini; dokumentasi menjelaskan tabel existing Modul 10 dan 12.

### Security
- Validasi request dan permission alias diperkuat untuk alur assessment dan finance.

### Known limitations
- Quality gate penuh dan acceptance visual perlu dijalankan di CI/staging dengan dependency lengkap dan database yang tersedia.
