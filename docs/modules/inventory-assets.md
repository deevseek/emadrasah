# Modul Inventaris dan Aset

Modul ini mengelola jenis barang, lokasi/ruangan, kondisi, satuan, data barang kuantitas maupun aset individual, saldo ledger, transaksi, stock opname, laporan, print, PDF, dan CSV. Role utama: super-admin/admin seluruh akses; kepala madrasah lihat-laporan-print-export-verifikasi; tata usaha/operator kelola master, barang, transaksi, mutasi, kondisi, stock opname; bendahara lihat laporan nilai perolehan.

Permission: `inventory.view`, `inventory.create`, `inventory.update`, `inventory.delete`, `inventory.manage-master`, `inventory.adjust`, `inventory.transfer`, `inventory.change-condition`, `inventory.stock-opname`, `inventory.verify`, `inventory.reverse`, `inventory.report`, `inventory.print`, `inventory.export`.

Workflow transaksi: draft konseptual langsung diposting oleh service untuk mencegah saldo menggantung; transaksi posted tidak diedit; pembatalan memakai reversal. Business rules: jumlah > 0, saldo tidak negatif, lokasi mutasi berbeda, kondisi baru berbeda, koreksi wajib alasan, saldo diubah melalui `InventoryLedgerService` dalam database transaction dan locking saldo.

Route utama: `/inventory/dashboard`, `/inventory/categories`, `/inventory/locations`, `/inventory/conditions`, `/inventory/items`, `/inventory/transactions`, `/inventory/stock-opnames`, `/inventory/reports`. Controller: `InventoryDashboardController`, `InventoryCrudController`, `InventoryTransactionController`, `InventoryStockOpnameController`, `InventoryReportController`. Service: `InventoryLedgerService`.

Laporan menyediakan tampilan web, print A4, endpoint PDF berbasis HTML, dan CSV UTF-8 sesuai filter kategori/lokasi/kondisi/status. Known limitation: PDF belum memakai renderer dompdf karena dependency baru tidak ditambahkan; respons PDF berisi HTML A4 dengan content type PDF agar siap diganti renderer saat dependency tersedia.
