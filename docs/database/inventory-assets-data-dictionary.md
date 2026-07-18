# Data Dictionary Inventaris dan Aset

| Tabel | Fungsi | Kolom Utama | Constraint/Index | Rule |
|---|---|---|---|---|
| `inventory_categories` | Master jenis barang | code, name, description, is_active, created_by, updated_by | unique code, soft delete | kategori terpakai tidak dihapus permanen |
| `inventory_locations` | Master lokasi/ruangan | code, name, type, building, floor, person_in_charge | unique code, index type/status | lokasi bersaldo dilindungi FK |
| `inventory_conditions` | Master kondisi terkontrol | code, name, severity, is_system | unique code | kondisi bukan teks bebas |
| `inventory_measurement_units` | Master satuan | code, name | unique code | satuan konsisten |
| `inventory_items` | Data barang/aset | code, category, unit, tracking_type, acquisition_value decimal | unique code, FK, index nama/status | mendukung quantity dan individual |
| `inventory_item_units` | Unit aset bernomor | unit_code, inventory_number, serial_number, location, condition | unique nomor, FK | unit punya satu lokasi/kondisi aktif |
| `inventory_balances` | Saldo agregat | item, location, condition, quantity | unique item+location+condition | performa; histori tetap transaksi |
| `inventory_transactions` | Ledger transaksi | number, date, type, item, lokasi/kondisi asal/tujuan, qty, reason | unique number, FK, index type/date | posted tidak dihapus; reversal/koreksi |
| `inventory_stock_opnames` | Sesi opname | number, location, date, status, officer | unique number | snapshot saldo |
| `inventory_stock_opname_lines` | Detail opname | item, condition, system, physical, difference | unique session+item+condition | selisih menjadi koreksi saat posting |
