# Modul Inventaris dan Aset

Modul ini mengelola jenis barang, lokasi/ruangan, kondisi, satuan, data barang kuantitas maupun aset individual, saldo ledger, transaksi, stock opname, laporan, print, PDF, dan CSV. Role utama: super-admin/admin seluruh akses; kepala madrasah lihat-laporan-print-export-verifikasi; tata usaha/operator kelola master, barang, transaksi, mutasi, kondisi, stock opname; bendahara lihat laporan nilai perolehan.

Permission: `inventory.view`, `inventory.create`, `inventory.update`, `inventory.delete`, `inventory.manage-master`, `inventory.adjust`, `inventory.transfer`, `inventory.change-condition`, `inventory.stock-opname`, `inventory.verify`, `inventory.reverse`, `inventory.report`, `inventory.print`, `inventory.export`.

Workflow transaksi: draft konseptual langsung diposting oleh service untuk mencegah saldo menggantung; transaksi posted tidak diedit; pembatalan memakai reversal. Business rules: jumlah > 0, saldo tidak negatif, lokasi mutasi berbeda, kondisi baru berbeda, koreksi wajib alasan, saldo diubah melalui `InventoryLedgerService` dalam database transaction dan locking saldo.

Route utama: `/inventory/dashboard`, `/inventory/categories`, `/inventory/locations`, `/inventory/conditions`, `/inventory/items`, `/inventory/transactions`, `/inventory/stock-opnames`, `/inventory/reports`. Controller: `InventoryDashboardController`, `InventoryCrudController`, `InventoryTransactionController`, `InventoryStockOpnameController`, `InventoryReportController`. Service: `InventoryLedgerService`.

Laporan menyediakan tampilan web, print A4, endpoint PDF berbasis HTML, dan CSV UTF-8 sesuai filter kategori/lokasi/kondisi/status. Known limitation: PDF belum memakai renderer dompdf karena dependency baru tidak ditambahkan; respons PDF berisi HTML A4 dengan content type PDF agar siap diganti renderer saat dependency tersedia.

## Integrasi Navigasi Sidebar

Grup **Inventaris** ditampilkan pada sidebar global setelah grup **Keuangan** dan sebelum **Akun & Akses**. Seluruh halaman inventaris memakai `x-inventory.layout` yang membungkus `x-app-layout`, sehingga sidebar, header, tahun ajaran, semester aktif, flash message, validasi, dan breadcrumb utama tetap konsisten.

| Menu | Route | URL | Permission visibilitas |
|---|---|---|---|
| Dashboard Inventaris | `inventory.dashboard` | `/inventory/dashboard` | `inventory.view` |
| Data Barang | `inventory.items.index` | `/inventory/items` | `inventory.view` |
| Kategori Barang | `inventory.categories.index` | `/inventory/categories` | `inventory.manage-master` |
| Lokasi & Ruangan | `inventory.locations.index` | `/inventory/locations` | `inventory.manage-master` |
| Kondisi Barang | `inventory.conditions.index` | `/inventory/conditions` | `inventory.manage-master` |
| Transaksi Inventaris | `inventory.transactions.index` | `/inventory/transactions` | salah satu `inventory.view`, `inventory.adjust`, `inventory.transfer`, `inventory.change-condition` |
| Stock Opname | `inventory.stock-opnames.index` | `/inventory/stock-opnames` | `inventory.stock-opname` |
| Laporan Inventaris | `inventory.reports.index` | `/inventory/reports` | salah satu `inventory.report`, `inventory.print`, `inventory.export` |

Role yang mendapat akses dari seeder idempotent: `super-admin`, `admin-madrasah`, `kepala-madrasah`, `tata-usaha`, `operator`, dan `bendahara`. Jalankan ulang permission dengan `php artisan db:seed --class=InventoryPermissionSeeder --force`, lalu bersihkan cache permission jika perlu.

### Troubleshooting menu tidak muncul

1. Pastikan production sudah pull branch `main` terbaru.
2. Pastikan migration inventaris sudah dijalankan.
3. Jalankan `php artisan db:seed --class=InventoryPermissionSeeder --force`.
4. Pastikan pengguna memiliki minimal `inventory.view` atau permission Inventaris lain yang sesuai menu.
5. Bersihkan permission cache dengan `php artisan permission:cache-reset` atau `app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();`.
6. Jalankan `php artisan optimize:clear` dan `php artisan view:clear`.
7. Bangun ulang route cache dengan `php artisan route:cache` setelah route valid.
8. Lakukan hard refresh browser (`Ctrl + Shift + R`).
