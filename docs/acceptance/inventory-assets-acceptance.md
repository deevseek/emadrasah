# Acceptance Modul Inventaris dan Aset

| Skenario | Role | Expected | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Master kategori/lokasi/satuan | Admin | CRUD dan duplikasi kode ditolak | Tersedia via route dan validasi unique | Siap uji manual | Browser tidak dijalankan |
| Barang kuantitas Kursi Siswa | Admin/TU | saldo awal, tambah, mutasi, perubahan kondisi menjaga saldo | Service dan feature test mencakup alur utama | Lulus otomatis bila test berjalan | Perlu verifikasi browser |
| Aset individual Laptop Guru | Admin/TU | unit individual punya nomor dan histori | Schema unit tersedia | Parsial | UI unit individual khusus belum dipisah |
| Stock opname | TU | snapshot, input fisik, posting koreksi | Controller dan service tersedia | Siap uji manual | |
| Laporan | Kepala/Admin | filter, print, PDF, CSV sesuai data | Web/print/PDF/CSV tersedia | Siap uji | PDF HTML |
| Authorization | Semua role | URL langsung ditolak | Middleware permission di route | Siap uji otomatis/manual | |
