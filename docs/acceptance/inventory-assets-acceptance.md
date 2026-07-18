# Acceptance Modul Inventaris dan Aset

| Skenario | Role | Expected | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Master kategori/lokasi/satuan | Admin | CRUD dan duplikasi kode ditolak | Tersedia via route dan validasi unique | Siap uji manual | Browser tidak dijalankan |
| Barang kuantitas Kursi Siswa | Admin/TU | saldo awal, tambah, mutasi, perubahan kondisi menjaga saldo | Service dan feature test mencakup alur utama | Lulus otomatis bila test berjalan | Perlu verifikasi browser |
| Aset individual Laptop Guru | Admin/TU | unit individual punya nomor dan histori | Schema unit tersedia | Parsial | UI unit individual khusus belum dipisah |
| Stock opname | TU | snapshot, input fisik, posting koreksi | Controller dan service tersedia | Siap uji manual | |
| Laporan | Kepala/Admin | filter, print, PDF, CSV sesuai data | Web/print/PDF/CSV tersedia | Siap uji | PDF HTML |
| Authorization | Semua role | URL langsung ditolak | Middleware permission di route | Siap uji otomatis/manual | |
| Sidebar Inventaris Super Admin | super-admin | Grup Inventaris dan 8 menu tampil dengan link valid | Dicakup test navigasi; perlu screenshot production | Siap uji | Active state per route |
| Breadcrumb Inventaris | super-admin | Breadcrumb Beranda / Inventaris / halaman tampil | Dicakup test navigasi | Siap uji | Menggunakan app-layout global |
| Akses tanpa permission | user tanpa permission Inventaris | Grup tidak tampil dan URL langsung 403 | Dicakup test navigasi | Siap uji otomatis | |
| Production hard refresh | super-admin | Menu tetap muncul setelah cache dibangun ulang | Perlu uji manual production | Belum dijalankan di container | Butuh server production |
