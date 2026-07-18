# Test Report Inventaris dan Aset

Tanggal: 18 Juli 2026. Environment: container `/workspace/emadrasah`. Pemeriksaan awal `git fetch origin` gagal karena remote `origin` tidak tersedia. `composer install` terkendala proxy GitHub 403 sehingga vendor tidak lengkap pada awal sesi; quality gate akhir dicatat faktual di laporan akhir.

Automated test ditambahkan: CRUD barang, validasi saldo ledger (saldo awal, mutasi, perubahan kondisi), saldo tidak cukup, export CSV, dan perhitungan selisih stock opname.

## Cakupan Test Navigasi

Feature test `InventoryNavigationTest` memverifikasi Super Admin melihat grup **Inventaris**, menu utama memakai route valid, breadcrumb Inventaris tampil, active state hanya aktif pada menu saat ini, sidebar tidak berisi `href="#"`, user tanpa permission tidak melihat grup Inventaris, URL langsung menghasilkan HTTP 403, serta variasi visibilitas menu untuk `tata-usaha`, `kepala-madrasah`, dan `bendahara`.

Command yang wajib dijalankan pada CI/staging: `php artisan test --filter=Inventory`, `php artisan test`, `vendor/bin/pint --test`, dan `npm run build`.
